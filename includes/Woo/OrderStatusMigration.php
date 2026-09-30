<?php
/**
 * Repairs order statuses truncated by older plugin versions.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Woo;

use Automattic\WooCommerce\Caches\OrderCache;

/**
 * Rewrites the truncated slugs in OrderStatusManager::LEGACY_SLUGS to their
 * current slug in every table that stores an order status.
 *
 * Up to 1.1.0 two custom slugs were longer than the varchar(20) status column:
 * under HPOS MySQL cut them, leaving orders in an unregistered status. Only the
 * exact truncated values are touched, so running this again is a no-op.
 */
final class OrderStatusMigration {

	/**
	 * Run the repair.
	 *
	 * @return void
	 */
	public static function run(): void {
		global $wpdb;

		$order_ids = [];

		foreach ( OrderStatusManager::LEGACY_SLUGS as $old => $new ) {
			$order_ids = array_merge(
				$order_ids,
				self::repair( $wpdb->prefix . 'wc_orders', 'status', 'id', $old, $new ),
				self::repair( $wpdb->posts, 'post_status', 'ID', $old, $new, "AND post_type IN ( 'shop_order', 'shop_order_placehold' )" ),
				self::repair( $wpdb->prefix . 'wc_order_stats', 'status', 'order_id', $old, $new )
			);
		}

		if ( [] !== $order_ids ) {
			self::clear_caches( array_unique( $order_ids ) );
		}
	}

	/**
	 * Replace one status value in one table.
	 *
	 * @param string $table  Table name (internal, from $wpdb).
	 * @param string $column Status column.
	 * @param string $key    Order ID column.
	 * @param string $old    Truncated status to replace.
	 * @param string $new    Current status.
	 * @param string $extra  Extra constant WHERE clause.
	 * @return array<int, int> IDs of the repaired orders.
	 */
	private static function repair( string $table, string $column, string $key, string $old, string $new, string $extra = '' ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Table/column names and $extra are internal constants; one-off repair of rows WooCommerce CRUD cannot load as valid orders.
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			return [];
		}

		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT {$key} FROM {$table} WHERE {$column} = %s {$extra}", $old ) );

		if ( [] !== $ids ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET {$column} = %s WHERE {$column} = %s {$extra}", $new, $old ) );
		}
		// phpcs:enable

		return array_map( 'intval', $ids );
	}

	/**
	 * Drop cached copies of the repaired orders and the order counts.
	 *
	 * @param array<int, int> $order_ids Repaired order IDs.
	 * @return void
	 */
	private static function clear_caches( array $order_ids ): void {
		$order_cache = function_exists( 'wc_get_container' ) && class_exists( OrderCache::class )
			? wc_get_container()->get( OrderCache::class )
			: null;

		foreach ( $order_ids as $order_id ) {
			clean_post_cache( $order_id );

			if ( null !== $order_cache ) {
				$order_cache->remove( $order_id );
			}
		}

		if ( function_exists( 'wc_delete_shop_order_transients' ) ) {
			wc_delete_shop_order_transients();
		}
	}
}
