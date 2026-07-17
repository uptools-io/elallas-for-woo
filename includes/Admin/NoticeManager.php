<?php
/**
 * Admin notice manager.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Options;

/**
 * Shows a dismissible onboarding nudge when no withdrawal page is configured.
 */
final class NoticeManager {

	/**
	 * User-meta key marking the nudge as dismissed.
	 */
	private const DISMISS_KEY = 'lw_elallas_onboarding_dismissed';

	/**
	 * Constructor — registers notice + dismissal hooks.
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'render' ] );
		add_action( 'admin_post_elallas_dismiss_onboarding', [ $this, 'dismiss' ] );
	}

	/**
	 * Render the onboarding nudge.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( 0 !== (int) Options::get( 'withdrawal_page_id' ) ) {
			return;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISS_KEY, true ) ) {
			return;
		}

		$wizard  = add_query_arg( [ 'page' => 'elallas-for-woo-onboarding' ], admin_url( 'admin.php' ) );
		$dismiss = wp_nonce_url(
			add_query_arg( [ 'action' => 'elallas_dismiss_onboarding' ], admin_url( 'admin-post.php' ) ),
			'elallas_dismiss_onboarding'
		);
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Elállás for WooCommerce:', 'elallas-for-woo' ); ?></strong>
				<?php esc_html_e( 'Még nincs beállítva elállási oldal. Indítsd el a beüzemelést.', 'elallas-for-woo' ); ?>
				<a class="button button-primary" href="<?php echo esc_url( $wizard ); ?>">
					<?php esc_html_e( 'Beüzemelés indítása', 'elallas-for-woo' ); ?>
				</a>
				<a href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Elrejtés', 'elallas-for-woo' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Persist dismissal for the current user.
	 *
	 * @return void
	 */
	public function dismiss(): void {
		check_admin_referer( 'elallas_dismiss_onboarding' );

		if ( current_user_can( 'manage_woocommerce' ) ) {
			update_user_meta( get_current_user_id(), self::DISMISS_KEY, 1 );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
}
