<?php
/**
 * Plugin deactivator.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas;

/**
 * Handles plugin deactivation.
 */
final class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( Activator::CRON_HOOK );
		delete_transient( 'lw_elallas_flush_rewrite' );
		flush_rewrite_rules();
	}
}
