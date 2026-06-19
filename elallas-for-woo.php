<?php
/**
 * Plugin Name:       Elallas for WooCommerce
 * Plugin URI:        https://github.com/uptools-io/elallas-for-woo
 * Description:       Online withdrawal (elállás) button and case management for WooCommerce — compliant with Directive (EU) 2023/2673 and 415/2025. (XII. 23.) Korm. rendelet.
 * Version:           1.0.5
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            uptools.io
 * Author URI:        https://uptools.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       elallas-for-woo
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   9.9
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'ELALLAS_FOR_WOO_VERSION', '1.0.5' );
define( 'ELALLAS_FOR_WOO_FILE', __FILE__ );
define( 'ELALLAS_FOR_WOO_PATH', plugin_dir_path( __FILE__ ) );
define( 'ELALLAS_FOR_WOO_URL', plugin_dir_url( __FILE__ ) );

// Autoloader: local vendor (standalone/ZIP) or root Composer (dependency install).
if ( file_exists( ELALLAS_FOR_WOO_PATH . 'vendor/autoload.php' ) ) {
	require_once ELALLAS_FOR_WOO_PATH . 'vendor/autoload.php';
} elseif ( ! class_exists( Plugin::class ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p><strong>Elallas for WooCommerce:</strong> %s</p></div>',
				esc_html__( 'Autoloader not found. Please run "composer install" in the plugin directory, or re-install the plugin from a release ZIP.', 'elallas-for-woo' )
			);
		}
	);
	return;
}

// Declare HPOS (High-Performance Order Storage) compatibility.
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Activation/Deactivation hooks.
register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );

/**
 * Returns the main plugin instance.
 *
 * @return Plugin
 */
function elallas_for_woo(): Plugin {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Plugin();
	}

	return $instance;
}

// Initialize the plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\elallas_for_woo' );
