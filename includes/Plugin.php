<?php
/**
 * Main plugin class.
 *
 * Wires all modules onto WordPress/WooCommerce hooks. Each module registers
 * its own hooks in its constructor; this class only decides what to load.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas;

use LightweightPlugins\Elallas\Frontend\Shortcodes;
use LightweightPlugins\Elallas\Frontend\MyAccountEndpoint;
use LightweightPlugins\Elallas\Frontend\Assets;
use LightweightPlugins\Elallas\Woo\Hooks as WooHooks;
use LightweightPlugins\Elallas\Woo\OrderStatusManager;
use LightweightPlugins\Elallas\Emails\EmailManager;
use LightweightPlugins\Elallas\Pdf\DownloadHandler;
use LightweightPlugins\Elallas\Api\RestController;
use LightweightPlugins\Elallas\Blocks\BlockRegistrar;
use LightweightPlugins\Elallas\Cron\RetentionCleaner;
use LightweightPlugins\Elallas\Integrations\Invoicing;
use LightweightPlugins\Elallas\Integrations\Shipping;
use LightweightPlugins\Elallas\Integrations\Multilingual;
use LightweightPlugins\Elallas\Integrations\Elementor;
use LightweightPlugins\Elallas\Admin\AdminMenu;
use LightweightPlugins\Elallas\Admin\Settings\AccountEndpointSetting;
use LightweightPlugins\Elallas\Admin\ProductFields;
use LightweightPlugins\Elallas\Admin\TermFields;
use LightweightPlugins\Elallas\Admin\NoticeManager;
use LightweightPlugins\Elallas\Admin\OrderWithdrawalNotice;
use LightweightPlugins\Elallas\SiteManager\Integration as SiteManagerIntegration;
use LightweightPlugins\Elallas\CLI\Commands as CliCommands;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();

		if ( ! self::is_woocommerce_active() ) {
			add_action( 'admin_notices', [ $this, 'woocommerce_missing_notice' ] );
			return;
		}

		$this->init_frontend();
		$this->init_services();
		$this->init_integrations();

		if ( is_admin() ) {
			$this->init_admin();
		}

		SiteManagerIntegration::init();
		$this->init_cli();

		do_action( 'elallas_boot', $this );
	}

	/**
	 * Register WP-CLI commands.
	 *
	 * @return void
	 */
	private function init_cli(): void {
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'elallas', new CliCommands() );
		}
	}

	/**
	 * Core WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ $this, 'maybe_flush_rewrite' ], 20 );
		add_action( 'admin_init', [ Activator::class, 'maybe_upgrade' ] );
		add_filter(
			'plugin_action_links_' . plugin_basename( ELALLAS_FOR_WOO_FILE ),
			[ $this, 'add_settings_link' ]
		);
	}

	/**
	 * Front-end surfaces.
	 *
	 * @return void
	 */
	private function init_frontend(): void {
		new Shortcodes();
		new MyAccountEndpoint();
		new Assets();
		new WooHooks();
		new OrderStatusManager();
	}

	/**
	 * Emails, documents, REST API and blocks.
	 *
	 * @return void
	 */
	private function init_services(): void {
		new EmailManager();
		new DownloadHandler();
		new RestController();
		new BlockRegistrar();
		new RetentionCleaner();
	}

	/**
	 * Third-party integrations.
	 *
	 * @return void
	 */
	private function init_integrations(): void {
		new Invoicing();
		new Shipping();
		new Multilingual();
		new Elementor();
	}

	/**
	 * Admin UI. AdminMenu instantiates CaseActions internally.
	 *
	 * @return void
	 */
	private function init_admin(): void {
		new AdminMenu();
		new ProductFields();
		new TermFields();
		new NoticeManager();
		new OrderWithdrawalNotice();
		new AccountEndpointSetting();
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'elallas-for-woo',
			false,
			dirname( plugin_basename( ELALLAS_FOR_WOO_FILE ) ) . '/languages'
		);
	}

	/**
	 * Flush rewrite rules when needed — runs at init priority 20, i.e. after the
	 * endpoint registers at init:10, so a rebuild always includes the current slug.
	 *
	 * Two triggers:
	 *  1. Plugin update — a plugin update does NOT re-run activation, so any
	 *     endpoint/rewrite added or renamed in the new version would 404 until a
	 *     reactivation. Flushing once per version bump closes that gap without a
	 *     per-request cost (the version is stored, so it fires at most once).
	 *  2. A queued flush transient — set on activation and on an endpoint-slug
	 *     change made outside the WooCommerce settings screen (WP-CLI, code).
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite(): void {
		if ( get_option( 'lw_elallas_version' ) !== ELALLAS_FOR_WOO_VERSION ) {
			update_option( 'lw_elallas_version', ELALLAS_FOR_WOO_VERSION );
			flush_rewrite_rules();
			delete_transient( 'lw_elallas_flush_rewrite' );
			return;
		}

		if ( get_transient( 'lw_elallas_flush_rewrite' ) ) {
			flush_rewrite_rules();
			delete_transient( 'lw_elallas_flush_rewrite' );
		}
	}

	/**
	 * Add a settings link to the plugin row.
	 *
	 * @param array<string> $links Plugin links.
	 * @return array<string>
	 */
	public function add_settings_link( array $links ): array {
		$url  = admin_url( 'admin.php?page=elallas-for-woo-settings' );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Beállítások', 'elallas-for-woo' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice(): void {
		printf(
			'<div class="notice notice-error"><p><strong>Elállás for WooCommerce:</strong> %s</p></div>',
			esc_html__( 'A plugin működéséhez a WooCommerce telepítése és aktiválása szükséges.', 'elallas-for-woo' )
		);
	}

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}
}
