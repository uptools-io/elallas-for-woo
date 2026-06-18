<?php
/**
 * Product-level withdrawal exception fields.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Data\DefaultTexts;
use LightweightPlugins\Elallas\Options;

/**
 * Adds and saves the "withdrawal exception" product meta on the General product tab.
 */
final class ProductFields {

	/**
	 * Excluded meta key.
	 */
	private const META_EXCLUDED = Options::META_PREFIX . 'excluded';

	/**
	 * Exclusion reason meta key.
	 */
	private const META_REASON = Options::META_PREFIX . 'exclusion_reason';

	/**
	 * Constructor — registers product data hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'render_fields' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_fields' ] );
	}

	/**
	 * Available exclusion reasons keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	public static function reasons(): array {
		return [
			'unsealed'  => __( 'Bontatlan állapot szükséges', 'elallas-for-woo' ),
			'custom'    => __( 'Egyedi / személyre szabott termék', 'elallas-for-woo' ),
			'digital'   => __( 'Digitális tartalom', 'elallas-for-woo' ),
			'service'   => __( 'Szolgáltatás', 'elallas-for-woo' ),
			'hygiene'   => __( 'Higiéniai ok', 'elallas-for-woo' ),
			'perishable' => __( 'Romlandó termék', 'elallas-for-woo' ),
			'sealed'    => __( 'Zárt csomagolás felbontva', 'elallas-for-woo' ),
		];
	}

	/**
	 * Render the exclusion checkbox + reason select.
	 *
	 * @return void
	 */
	public function render_fields(): void {
		global $post;
		$product_id = $post instanceof \WP_Post ? (int) $post->ID : 0;
		echo '<div class="options_group">';

		woocommerce_wp_checkbox(
			[
				'id'          => self::META_EXCLUDED,
				'label'       => __( 'Elállásból kizárt', 'elallas-for-woo' ),
				'description' => __( 'A termék nem küldhető be online elállásra.', 'elallas-for-woo' ),
				'value'       => self::is_excluded( $product_id ) ? 'yes' : 'no',
			]
		);

		woocommerce_wp_select(
			[
				'id'      => self::META_REASON,
				'label'   => __( 'Kizárás indoka', 'elallas-for-woo' ),
				'options' => array_merge( [ '' => __( '— Válassz indokot —', 'elallas-for-woo' ) ], self::reasons() ),
				'value'   => (string) get_post_meta( $product_id, self::META_REASON, true ),
			]
		);

		printf( '<p class="description" style="padding:0 12px;">%s</p>', esc_html( DefaultTexts::exception_warning() ) );
		echo '</div>';
	}

	/**
	 * Save the exclusion meta.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function save_fields( int $product_id ): void {
		// woocommerce_process_product_meta verifies the product nonce upstream;
		// re-check capability before writing.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$excluded = isset( $_POST[ self::META_EXCLUDED ] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $product_id, self::META_EXCLUDED, $excluded );

		$reason = isset( $_POST[ self::META_REASON ] ) ? sanitize_key( wp_unslash( $_POST[ self::META_REASON ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$reason = array_key_exists( $reason, self::reasons() ) ? $reason : '';
		update_post_meta( $product_id, self::META_REASON, $reason );
	}

	/**
	 * Whether a product is excluded from withdrawal.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function is_excluded( int $product_id ): bool {
		return 'yes' === get_post_meta( $product_id, self::META_EXCLUDED, true );
	}

	/**
	 * Human label of the saved exclusion reason (or empty string).
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function exclusion_label( int $product_id ): string {
		$reason = (string) get_post_meta( $product_id, self::META_REASON, true );
		$labels = self::reasons();

		return $labels[ $reason ] ?? '';
	}
}
