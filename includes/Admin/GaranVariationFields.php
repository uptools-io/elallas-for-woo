<?php
/**
 * Per-variation GARAN override (inherit / own data / none).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

/**
 * Variation panel fields. `_lw_elallas_garan_enabled` on a variation:
 * '' = inherit the parent, 'yes' = own years/brand/model, 'no' = no label.
 * Own data goes through the same GaranData + GaranMetrics validation; an
 * invalid override falls back to "inherit" with an admin error.
 */
final class GaranVariationFields {

	/**
	 * Posted field names (distinct from the parent's meta-named inputs, which
	 * live in the same product form).
	 *
	 * @var array<string, string>
	 */
	private const INPUTS = [
		'enabled' => 'lw_elallas_vgaran_enabled',
		'years'   => 'lw_elallas_vgaran_years',
		'brand'   => 'lw_elallas_vgaran_brand',
		'model'   => 'lw_elallas_vgaran_model',
	];

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'render' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save' ], 10, 2 );
		add_action( 'admin_footer', [ $this, 'print_toggle_script' ] );
	}

	/**
	 * Render the variation fields.
	 *
	 * @param int   $loop           Variation index.
	 * @param mixed $variation_data Variation data (unused).
	 * @param mixed $variation      Variation post (\WP_Post).
	 * @return void
	 */
	public function render( $loop, $variation_data, $variation ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- Hook signature.
		$loop = (int) $loop;
		$id   = $variation instanceof \WP_Post ? (int) $variation->ID : 0;
		$raw  = GaranProductFields::raw( $id );

		echo '<div class="elallas-garan-variation" style="clear:both;">';
		woocommerce_wp_select(
			[
				'id'            => self::INPUTS['enabled'] . '_' . $loop,
				'name'          => self::INPUTS['enabled'] . '[' . $loop . ']',
				'label'         => __( 'GARAN címke', 'elallas-for-woo' ),
				'value'         => $raw['enabled'],
				'class'         => 'elallas-garan-variation-mode',
				'wrapper_class' => 'form-row form-row-full',
				'options'       => [
					''    => __( 'Öröklés a szülőtől', 'elallas-for-woo' ),
					'yes' => __( 'Saját adat', 'elallas-for-woo' ),
					'no'  => __( 'Nincs GARAN címke', 'elallas-for-woo' ),
				],
			]
		);

		$hidden = 'yes' === $raw['enabled'] ? '' : ' style="display:none"';
		echo '<div class="elallas-garan-variation-fields"' . $hidden . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Constant attribute.
		foreach ( GaranProductFields::text_fields() as $key => $field ) {
			woocommerce_wp_text_input(
				array_merge(
					$field,
					[
						'id'            => self::INPUTS[ $key ] . '_' . $loop,
						'name'          => self::INPUTS[ $key ] . '[' . $loop . ']',
						'value'         => $raw[ $key ],
						'wrapper_class' => 'form-row form-row-full',
						'desc_tip'      => false,
					]
				)
			);
		}
		echo '</div></div>';
	}

	/**
	 * Save one variation.
	 *
	 * @param int $variation_id Variation id.
	 * @param int $i            Loop index.
	 * @return void
	 */
	public function save( $variation_id, $i ): void {
		// Variation saves are nonce-checked by WooCommerce (save-variations); re-check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$i       = (int) $i;
		$enabled = self::posted( self::INPUTS['enabled'], $i );
		$enabled = in_array( $enabled, [ '', 'yes', 'no' ], true ) ? $enabled : '';
		$values  = [
			'years' => self::posted( self::INPUTS['years'], $i ),
			'brand' => self::posted( self::INPUTS['brand'], $i ),
			'model' => self::posted( self::INPUTS['model'], $i ),
		];

		$result = GaranProductFields::validate( 'yes' === $enabled, $values );
		if ( 'yes' === $enabled && 'yes' !== $result['enabled'] ) {
			$enabled = '';
			if ( class_exists( '\WC_Admin_Meta_Boxes' ) ) {
				\WC_Admin_Meta_Boxes::add_error(
					sprintf(
						/* translators: 1: variation id, 2: validation message */
						__( '#%1$d variáció: %2$s A variáció a szülő adatait örökli.', 'elallas-for-woo' ),
						(int) $variation_id,
						$result['error']
					)
				);
			}
		}

		GaranProductFields::store( (int) $variation_id, $enabled, $result['values'] );
		GaranProductFields::pregenerate( $enabled, $result['values'] );
	}

	/**
	 * Show the three fields only for "own data" (variation panels load via AJAX).
	 *
	 * @return void
	 */
	public function print_toggle_script(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}
		?>
		<script>
		(function () {
			document.addEventListener('change', function (e) {
				var el = e.target;
				if (!el.classList || !el.classList.contains('elallas-garan-variation-mode')) { return; }
				var box = el.closest('.elallas-garan-variation');
				var fields = box ? box.querySelector('.elallas-garan-variation-fields') : null;
				if (fields) { fields.style.display = el.value === 'yes' ? '' : 'none'; }
			});
		})();
		</script>
		<?php
	}

	/**
	 * Cleaned posted value of an indexed field.
	 *
	 * @param string $key Field name.
	 * @param int    $i   Index.
	 * @return string
	 */
	private static function posted( string $key, int $i ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked by WooCommerce; sanitised in clean().
		$raw = isset( $_POST[ $key ][ $i ] ) && is_scalar( $_POST[ $key ][ $i ] ) ? (string) wp_unslash( $_POST[ $key ][ $i ] ) : '';

		return GaranProductFields::clean( $raw );
	}
}
