<?php
/**
 * Uninstall script.
 *
 * Removes plugin data only when the "remove data on uninstall" option is enabled.
 *
 * @package LightweightPlugins\Elallas
 */

// Prevent direct access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$lw_elallas_options = get_option( 'lw_elallas_options', [] );
$lw_elallas_cleanup = is_array( $lw_elallas_options ) && ! empty( $lw_elallas_options['uninstall_remove_data'] );

if ( ! $lw_elallas_cleanup ) {
	return;
}

global $wpdb;

// Drop custom tables.
$lw_elallas_tables = [
	$wpdb->prefix . 'lw_elallas_cases',
	$wpdb->prefix . 'lw_elallas_case_items',
	$wpdb->prefix . 'lw_elallas_events',
	$wpdb->prefix . 'lw_elallas_documents',
];

foreach ( $lw_elallas_tables as $lw_elallas_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$lw_elallas_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
}

// Delete options.
delete_option( 'lw_elallas_options' );
delete_option( 'lw_elallas_db_version' );
delete_option( 'lw_elallas_case_counter' );

// Delete transients.
delete_transient( 'lw_elallas_flush_rewrite' );

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'lw_elallas_daily_retention_cleanup' );

// Remove order meta.
delete_post_meta_by_key( '_lw_elallas_has_case' );
delete_post_meta_by_key( '_lw_elallas_case_ids' );
delete_post_meta_by_key( '_lw_elallas_deadline_status' );

// Remove product meta (withdrawal exception + GARAN label, incl. variations).
foreach ( [ '_lw_elallas_excluded', '_lw_elallas_exclusion_reason', '_lw_elallas_garan_enabled', '_lw_elallas_garan_years', '_lw_elallas_garan_brand', '_lw_elallas_garan_model' ] as $lw_elallas_meta_key ) {
	delete_post_meta_by_key( $lw_elallas_meta_key );
}

// Remove the GARAN order item snapshots.
$wpdb->delete( $wpdb->prefix . 'woocommerce_order_itemmeta', [ 'meta_key' => '_lw_elallas_garan' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off uninstall cleanup of our own item meta; WC has no bulk API.

// Compliance admin state.
delete_metadata( 'user', 0, 'lw_elallas_compliance_dismissed', '', true );
delete_transient( 'lw_elallas_checkout_fallback' );
delete_transient( 'lw_elallas_garan_integrity_error' );

// Generated GARAN e-mail images (uploads/elallas-garan/, flat directory).
$lw_elallas_uploads = wp_upload_dir( null, false );
$lw_elallas_garan   = trailingslashit( $lw_elallas_uploads['basedir'] ) . 'elallas-garan';
if ( is_dir( $lw_elallas_garan ) ) {
	foreach ( (array) glob( $lw_elallas_garan . '/*' ) as $lw_elallas_file ) {
		if ( is_string( $lw_elallas_file ) && is_file( $lw_elallas_file ) ) {
			wp_delete_file( $lw_elallas_file );
		}
	}
	@rmdir( $lw_elallas_garan ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Empty plugin-owned directory.
}
