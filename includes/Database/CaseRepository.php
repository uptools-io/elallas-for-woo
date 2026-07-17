<?php
/**
 * Case repository.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Database;

use LightweightPlugins\Elallas\Models\WithdrawalCase;
use LightweightPlugins\Elallas\Models\CaseStatus;

/**
 * Database operations for withdrawal cases.
 */
final class CaseRepository {

	/**
	 * Insert a case.
	 *
	 * @param array<string, mixed> $data Case data.
	 * @return int Inserted ID (0 on failure).
	 */
	public static function insert( array $data ): int {
		global $wpdb;

		$now                = current_time( 'mysql' );
		$data['created_at'] = $data['created_at'] ?? $now;
		$data['updated_at'] = $now;

		$result = $wpdb->insert( Schema::cases_table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update a case.
	 *
	 * @param int                  $id   Case ID.
	 * @param array<string, mixed> $data Fields to update.
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update( Schema::cases_table(), $data, [ 'id' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return false !== $result;
	}

	/**
	 * Update case status.
	 *
	 * @param int    $id     Case ID.
	 * @param string $status New status.
	 * @return bool
	 */
	public static function update_status( int $id, string $status ): bool {
		return self::update( $id, [ 'status' => $status ] );
	}

	/**
	 * Find a case by ID.
	 *
	 * @param int $id Case ID.
	 * @return WithdrawalCase|null
	 */
	public static function find( int $id ): ?WithdrawalCase {
		global $wpdb;

		$table = Schema::cases_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query on the plugin's custom table; results are not object-cached.
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);

		return $row ? WithdrawalCase::from_row( $row ) : null;
	}

	/**
	 * Find a case by case number.
	 *
	 * @param string $number Case number.
	 * @return WithdrawalCase|null
	 */
	public static function find_by_number( string $number ): ?WithdrawalCase {
		global $wpdb;

		$table = Schema::cases_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query on the plugin's custom table; results are not object-cached.
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE case_number = %s", $number ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);

		return $row ? WithdrawalCase::from_row( $row ) : null;
	}

	/**
	 * Find all cases for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array<int, WithdrawalCase>
	 */
	public static function find_by_order( int $order_id ): array {
		global $wpdb;

		$table = Schema::cases_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query on the plugin's custom table; results are not object-cached.
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY created_at DESC", $order_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);

		return array_map( [ WithdrawalCase::class, 'from_row' ], $results );
	}

	/**
	 * Find all cases belonging to a customer.
	 *
	 * @param int $customer_id Customer user ID.
	 * @return array<int, WithdrawalCase>
	 */
	public static function find_by_customer( int $customer_id ): array {
		global $wpdb;

		$table = Schema::cases_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query on the plugin's custom table; results are not object-cached.
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE customer_id = %d ORDER BY created_at DESC", $customer_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);

		return array_map( [ WithdrawalCase::class, 'from_row' ], $results );
	}

	/**
	 * Whether the order has a non-terminal (open) case.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public static function exists_open_for_order( int $order_id ): bool {
		foreach ( self::find_by_order( $order_id ) as $case ) {
			if ( ! CaseStatus::is_terminal( $case->status ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Query cases with filters and pagination (delegates to CaseQuery).
	 *
	 * @param array<string, mixed> $filters  Filters: status, deadline_status, withdrawal_type, search, date_from, date_to.
	 * @param int                  $paged    Page number (1-based).
	 * @param int                  $per_page Items per page.
	 * @param string               $orderby  Column to sort by.
	 * @param string               $order    ASC or DESC.
	 * @return array{items: array<int, WithdrawalCase>, total: int}
	 */
	public static function query( array $filters, int $paged = 1, int $per_page = 20, string $orderby = 'created_at', string $order = 'DESC' ): array {
		return CaseQuery::run( $filters, $paged, $per_page, $orderby, $order );
	}

	/**
	 * Count cases grouped by status.
	 *
	 * @return array<string, int>
	 */
	public static function count_by_status(): array {
		return CaseQuery::count_by_status();
	}

	/**
	 * IDs of cases older than N days that still hold personal data.
	 *
	 * Used by the retention cleanup to anonymize aged cases.
	 *
	 * @param int $days Retention period in days.
	 * @return array<int, int>
	 */
	public static function ids_older_than( int $days ): array {
		global $wpdb;

		$table  = Schema::cases_table();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query on the plugin's custom table; results are not object-cached.
		$results = $wpdb->get_col(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE created_at < %s AND customer_email_hash <> ''", $cutoff ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);

		return array_map( 'intval', (array) $results );
	}
}
