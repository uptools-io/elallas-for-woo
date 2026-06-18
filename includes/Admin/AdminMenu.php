<?php
/**
 * Admin menu registration.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Admin\Onboarding\Wizard;
use LightweightPlugins\Elallas\Admin\Settings\SettingsPage;

/**
 * Registers the WooCommerce submenu pages and renders the cases screen.
 */
final class AdminMenu {

	/**
	 * Cases page slug.
	 */
	public const SLUG = 'elallas-for-woo';

	/**
	 * Constructor — registers menu hooks and admin-post handlers.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register' ], 10 );
		new CaseActions();
	}

	/**
	 * Register the submenu pages under WooCommerce.
	 *
	 * @return void
	 */
	public function register(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Elállási ügyek', 'elallas-for-woo' ),
			__( 'Elállási ügyek', 'elallas-for-woo' ),
			'manage_woocommerce',
			self::SLUG,
			[ $this, 'render_cases' ]
		);

		add_submenu_page(
			'woocommerce',
			__( 'Elállás beállítások', 'elallas-for-woo' ),
			__( 'Elállás – beállítások', 'elallas-for-woo' ),
			'manage_woocommerce',
			SettingsPage::SLUG,
			[ SettingsPage::class, 'render' ]
		);

		add_submenu_page(
			'',
			__( 'Elállás beüzemelése', 'elallas-for-woo' ),
			'',
			'manage_woocommerce',
			'elallas-for-woo-onboarding',
			[ Wizard::class, 'render' ]
		);
	}

	/**
	 * Render the cases screen (list or single-case detail).
	 *
	 * @return void
	 */
	public function render_cases(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'elallas-for-woo' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		$case_id = isset( $_GET['case_id'] ) ? absint( $_GET['case_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( 'case' === $view && $case_id > 0 ) {
			CaseDetailPage::render( $case_id );
			return;
		}

		$this->render_list();
	}

	/**
	 * Render the cases list table with filters.
	 *
	 * @return void
	 */
	private function render_list(): void {
		$table = new CasesListTable();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Elállási ügyek', 'elallas-for-woo' ); ?></h1>
			<hr class="wp-header-end" />
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
				<?php
				$table->search_box( __( 'Keresés', 'elallas-for-woo' ), 'elallas-case-search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}
}
