<?php
/**
 * Case item repository.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Database;

use LightweightPlugins\Elallas\Models\CaseItem;

/**
 * Database operations for case items (order snapshots).
 */
final class CaseItemRepository {

	/**
	 * Insert a single case item.
	 *
	 * @param array<string, mixed> $data Item data.
	 * @return int Inserted ID (0 on failure).
	 */
	public static function insert( array $data ): int {
		global $wpdb;

		$result = $wpdb->insert( Schema::case_items_table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Insert multiple items for a case.
	 *
	 * @param int                        $case_id Case ID.
	 * @param array<int, array<string, mixed>> $items   Item rows (without case_id).
	 * @return void
	 */
	public static function bulk_insert( int $case_id, array $items ): void {
		foreach ( $items as $item ) {
			$item['case_id'] = $case_id;
			self::insert( $item );
		}
	}

	/**
	 * Get items for a case.
	 *
	 * @param int $case_id Case ID.
	 * @return array<int, CaseItem>
	 */
	public static function for_case( int $case_id ): array {
		global $wpdb;

		$table   = Schema::case_items_table();
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE case_id = %d ORDER BY id ASC", $case_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);

		return array_map( [ CaseItem::class, 'from_row' ], $results );
	}
}
