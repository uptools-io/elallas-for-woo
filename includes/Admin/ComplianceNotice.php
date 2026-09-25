<?php
/**
 * Admin deadline / configuration notice for the compliance module.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Admin\Onboarding\PageCreator;
use LightweightPlugins\Elallas\Admin\Settings\SettingsPage;
use LightweightPlugins\Elallas\Compliance\CheckoutSlot;
use LightweightPlugins\Elallas\Compliance\OfficialAssets;
use LightweightPlugins\Elallas\Options;

/**
 * Deadline warning (30-day snooze, date-dependent text), official-file
 * integrity and block-checkout fallback errors, and the "create notice page"
 * admin-post handler.
 */
final class ComplianceNotice {

	/**
	 * User meta holding the dismissal timestamp.
	 */
	public const DISMISS_KEY = 'lw_elallas_compliance_dismissed';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'render' ] );
		add_action( 'admin_post_elallas_dismiss_compliance', [ $this, 'dismiss' ] );
		add_action( 'admin_post_elallas_create_notice_page', [ $this, 'create_notice_page' ] );
	}

	/**
	 * Render the notices.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! self::garan_files_intact() ) {
			self::error( __( 'A hivatalos GARAN címkefájl sérült vagy módosult; a címke nem jelenik meg. Telepítsd újra a plugint.', 'elallas-for-woo' ) );
		}

		if ( get_transient( CheckoutSlot::FALLBACK_TRANSIENT ) ) {
			self::error( __( 'A GARAN és az értesítés a blokkos pénztárban nem a rendelés gomb előtt jelenik meg. Lásd a hibaelhárítási útmutatót.', 'elallas-for-woo' ) );
		}

		$problems = ComplianceCheck::problems( Options::get_all(), has_filter( 'elallas_garan_checkout_visible' ) );

		if ( [] === $problems || ComplianceCheck::is_snoozed( (int) get_user_meta( get_current_user_id(), self::DISMISS_KEY, true ), time() ) ) {
			return;
		}

		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=elallas_dismiss_compliance' ), 'elallas_dismiss_compliance' );
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Elállás for WooCommerce:', 'elallas-for-woo' ); ?></strong>
				<?php echo esc_html( ComplianceCheck::message( $problems, current_time( 'Y-m-d' ) ) ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( self::tab_url() ); ?>"><?php esc_html_e( 'Beállítások megnyitása', 'elallas-for-woo' ); ?></a>
				<a href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Elrejtés 30 napra', 'elallas-for-woo' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Snooze the warning for 30 days for the current user.
	 *
	 * @return void
	 */
	public function dismiss(): void {
		check_admin_referer( 'elallas_dismiss_compliance' );

		if ( current_user_can( 'manage_woocommerce' ) ) {
			update_user_meta( get_current_user_id(), self::DISMISS_KEY, time() );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Create the standalone notice page (/szavatossag/) and return to the tab.
	 *
	 * @return void
	 */
	public function create_notice_page(): void {
		check_admin_referer( 'elallas_create_notice_page' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez a művelethez.', 'elallas-for-woo' ), '', [ 'response' => 403 ] );
		}

		$page_id = PageCreator::create_for(
			'notice_page_id',
			__( 'Szavatosság', 'elallas-for-woo' ),
			'[elallas_guarantee_notice mode="inline"]',
			'szavatossag'
		);

		wp_safe_redirect( add_query_arg( 'created', $page_id > 0 ? '1' : '0', self::tab_url() ) );
		exit;
	}

	/**
	 * Whether both bundled GARAN SVGs match their official sha256 (memoised per request).
	 *
	 * @return bool
	 */
	public static function garan_files_intact(): bool {
		static $intact = null;

		if ( null === $intact ) {
			$intact = true;
			foreach ( OfficialAssets::GARAN_SVG as $relpath ) {
				$file     = ELALLAS_FOR_WOO_PATH . $relpath;
				$expected = OfficialAssets::SVG_SHA256[ basename( $relpath ) ] ?? '';
				if ( ! is_readable( $file ) || ! hash_equals( $expected, (string) hash_file( 'sha256', $file ) ) ) {
					$intact = false;
				}
			}
		}

		return $intact;
	}

	/**
	 * URL of the "Szavatosság és GARAN" settings tab.
	 *
	 * @return string
	 */
	public static function tab_url(): string {
		return add_query_arg(
			[
				'page' => SettingsPage::SLUG,
				'tab'  => 'compliance',
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Print a non-dismissible error notice.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private static function error( string $message ): void {
		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
			esc_html__( 'Elállás for WooCommerce:', 'elallas-for-woo' ),
			esc_html( $message )
		);
	}
}
