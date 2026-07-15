<?php
/**
 * Plugin Name:       Elállás for WooCommerce
 * Plugin URI:        https://github.com/uptools-io/elallas-for-woo
 * Description:       Online withdrawal (elállás) button and case management for WooCommerce — compliant with Directive (EU) 2023/2673 and 415/2025. (XII. 23.) Korm. rendelet.
 * Version:           1.0.13
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
define( 'ELALLAS_FOR_WOO_VERSION', '1.0.13' );
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
				'<div class="notice notice-error"><p><strong>Elállás for WooCommerce:</strong> %s</p></div>',
				esc_html__( 'Az automatikus betöltő (autoloader) nem található. Futtasd a „composer install” parancsot a plugin könyvtárában, vagy telepítsd újra a plugint egy kiadási ZIP-ből.', 'elallas-for-woo' )
			);
		}
	);
	return;
}

// Scoped (Strauss-prefixed) dependencies — present in release builds. The bundled
// Dompdf lives under the LightweightPlugins\Elallas\Vendor\ namespace so it can never
// collide with a Dompdf shipped by another plugin. Absent in Composer-dependency
// installs, where the host project's autoloader resolves Dompdf instead.
if ( file_exists( ELALLAS_FOR_WOO_PATH . 'vendor-prefixed/autoload.php' ) ) {
	require_once ELALLAS_FOR_WOO_PATH . 'vendor-prefixed/autoload.php';
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
