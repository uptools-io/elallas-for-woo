<?php
/**
 * Plugin activator.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas;

use LightweightPlugins\Elallas\Database\Schema;

/**
 * Handles plugin activation and DB upgrades.
 */
final class Activator {

	/**
	 * DB version option key.
	 */
	private const DB_VERSION_KEY = 'lw_elallas_db_version';

	/**
	 * Current DB version.
	 */
	private const DB_VERSION = '1.0.5';

	/**
	 * Daily retention cron hook.
	 */
	public const CRON_HOOK = 'lw_elallas_daily_retention_cleanup';

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_db_version();
		self::create_documents_dir();
		self::schedule_cron();

		// Record the version so the init:20 flush treats this as up-to-date and
		// relies on the transient below (rather than firing an extra version flush).
		update_option( 'lw_elallas_version', ELALLAS_FOR_WOO_VERSION );

		set_transient( 'lw_elallas_flush_rewrite', 1, MINUTE_IN_SECONDS );
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( Schema::get_cases_sql() );
		dbDelta( Schema::get_case_items_sql() );
		dbDelta( Schema::get_events_sql() );
		dbDelta( Schema::get_documents_sql() );
	}

	/**
	 * Create the protected documents directory.
	 *
	 * @return void
	 */
	private static function create_documents_dir(): void {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'elallas-docs';

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Order Deny,Allow\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		$index = trailingslashit( $dir ) . 'index.html';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}

	/**
	 * Schedule the retention cleanup cron.
	 *
	 * @return void
	 */
	private static function schedule_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Set the database version.
	 *
	 * @return void
	 */
	private static function set_db_version(): void {
		update_option( self::DB_VERSION_KEY, self::DB_VERSION );
	}

	/**
	 * Get the current DB version.
	 *
	 * @return string
	 */
	public static function get_db_version(): string {
		return (string) get_option( self::DB_VERSION_KEY, '0.0.0' );
	}

	/**
	 * Whether a DB upgrade is needed.
	 *
	 * @return bool
	 */
	public static function needs_upgrade(): bool {
		return version_compare( self::get_db_version(), self::DB_VERSION, '<' );
	}

	/**
	 * Run upgrade if needed.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		if ( self::needs_upgrade() ) {
			self::create_tables();
			self::set_db_version();
		}
	}
}
