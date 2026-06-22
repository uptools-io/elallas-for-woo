<?php
/**
 * Category / tag level withdrawal exception fields (term meta).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Options;

/**
 * Adds an "excluded from withdrawal" checkbox + reason to the product category
 * and product tag add/edit screens, stored as term meta.
 */
final class TermFields {

	/**
	 * Excluded term-meta key.
	 */
	private const META_EXCLUDED = Options::META_PREFIX . 'excluded';

	/**
	 * Reason term-meta key.
	 */
	private const META_REASON = Options::META_PREFIX . 'exclusion_reason';

	/**
	 * Nonce action/field name.
	 */
	private const NONCE = 'elallas_term_exception';

	/**
	 * Taxonomies that support withdrawal exceptions.
	 *
	 * @var array<int, string>
	 */
	private const TAXONOMIES = [ 'product_cat', 'product_tag' ];

	/**
	 * Constructor — registers the taxonomy form hooks.
	 */
	public function __construct() {
		foreach ( self::TAXONOMIES as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", [ $this, 'render_add' ] );
			add_action( "{$taxonomy}_edit_form_fields", [ $this, 'render_edit' ] );
			add_action( "created_{$taxonomy}", [ $this, 'save' ] );
			add_action( "edited_{$taxonomy}", [ $this, 'save' ] );
		}
	}

	/**
	 * Whether a term is marked as excluded from withdrawal.
	 *
	 * @param int $term_id Term ID.
	 * @return bool
	 */
	public static function is_excluded_term( int $term_id ): bool {
		return 'yes' === get_term_meta( $term_id, self::META_EXCLUDED, true );
	}

	/**
	 * Human reason label stored on a term (or empty string).
	 *
	 * @param int $term_id Term ID.
	 * @return string
	 */
	public static function term_reason_label( int $term_id ): string {
		$reason = (string) get_term_meta( $term_id, self::META_REASON, true );
		$labels = ProductFields::reasons();

		return $labels[ $reason ] ?? '';
	}

	/**
	 * Render the fields on the "add term" screen.
	 *
	 * @return void
	 */
	public function render_add(): void {
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<div class="form-field">
			<label>
				<input type="checkbox" name="<?php echo esc_attr( self::META_EXCLUDED ); ?>" value="yes" />
				<?php esc_html_e( 'Elállásból kizárt', 'elallas-for-woo' ); ?>
			</label>
			<p><?php esc_html_e( 'Az ebbe a kategóriába/címkébe tartozó termékek "kizárt"-ként jelölődnek az elállási ügyekben.', 'elallas-for-woo' ); ?></p>
			<?php $this->reason_select( '' ); ?>
		</div>
		<?php
	}

	/**
	 * Render the fields on the "edit term" screen.
	 *
	 * @param \WP_Term $term Term being edited.
	 * @return void
	 */
	public function render_edit( \WP_Term $term ): void {
		$excluded = self::is_excluded_term( (int) $term->term_id );
		$reason   = (string) get_term_meta( (int) $term->term_id, self::META_REASON, true );
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Elállásból kizárt', 'elallas-for-woo' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( self::META_EXCLUDED ); ?>" value="yes" <?php checked( $excluded, true ); ?> />
					<?php esc_html_e( 'A kategóriába/címkébe tartozó termékek kizárása az online elállásból', 'elallas-for-woo' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Az érintett tételek "kizárt"-ként jelölődnek az elállási ügyekben (jelölés, nem automatikus tiltás).', 'elallas-for-woo' ); ?></p>
				<p><?php $this->reason_select( $reason ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the term exception meta.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save( int $term_id ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		update_term_meta( $term_id, self::META_EXCLUDED, isset( $_POST[ self::META_EXCLUDED ] ) ? 'yes' : 'no' );

		$reason = isset( $_POST[ self::META_REASON ] ) ? sanitize_key( wp_unslash( $_POST[ self::META_REASON ] ) ) : '';
		$reason = array_key_exists( $reason, ProductFields::reasons() ) ? $reason : '';
		update_term_meta( $term_id, self::META_REASON, $reason );
	}

	/**
	 * Render the exclusion-reason select.
	 *
	 * @param string $selected Currently selected reason key.
	 * @return void
	 */
	private function reason_select( string $selected ): void {
		printf( '<label for="%1$s">%2$s</label> ', esc_attr( self::META_REASON ), esc_html__( 'Kizárás indoka:', 'elallas-for-woo' ) );
		printf( '<select name="%1$s" id="%1$s">', esc_attr( self::META_REASON ) );
		printf( '<option value="">%s</option>', esc_html__( '— Válassz indokot —', 'elallas-for-woo' ) );
		foreach ( ProductFields::reasons() as $key => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( (string) $key ), selected( $selected, (string) $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}
}
