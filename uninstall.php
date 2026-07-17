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
delete_option( 'lw_elallas_version' );
delete_option( 'lw_elallas_case_counter' );
delete_option( 'woocommerce_myaccount_withdrawals_endpoint' );

// Delete transients.
delete_transient( 'lw_elallas_flush_rewrite' );

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'lw_elallas_daily_retention_cleanup' );

// Remove order meta.
delete_post_meta_by_key( '_lw_elallas_has_case' );
delete_post_meta_by_key( '_lw_elallas_case_ids' );
delete_post_meta_by_key( '_lw_elallas_deadline_status' );
