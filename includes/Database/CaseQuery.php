<?php
/**
 * Case listing/reporting queries.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Database;

use LightweightPlugins\Elallas\Models\WithdrawalCase;

/**
 * Filtered, paginated case queries and aggregate counts.
 */
final class CaseQuery {

	/**
	 * Query cases with filters and pagination.
	 *
	 * @param array<string, mixed> $filters  Filters: status, deadline_status, withdrawal_type, search, date_from, date_to.
	 * @param int                  $paged    Page number (1-based).
	 * @param int                  $per_page Items per page.
	 * @param string               $orderby  Column to sort by.
	 * @param string               $order    ASC or DESC.
	 * @return array{items: array<int, WithdrawalCase>, total: int}
	 */
	public static function run( array $filters, int $paged = 1, int $per_page = 20, string $orderby = 'created_at', string $order = 'DESC' ): array {
		global $wpdb;

		$table  = Schema::cases_table();
		$where  = [ '1=1' ];
		$params = [];

		foreach ( [
			'status'          => 'status',
			'deadline_status' => 'deadline_status',
			'withdrawal_type' => 'withdrawal_type',
		] as $key => $column ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$where[]  = "{$column} = %s";
				$params[] = (string) $filters[ $key ];
			}
		}

		if ( ! empty( $filters['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( (string) $filters['search'] ) . '%';
			$where[]  = '(case_number LIKE %s OR order_number LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = (string) $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = (string) $filters['date_to'];
		}

		$where_sql      = implode( ' AND ', $where );
		$allowed_orders = [ 'created_at', 'submitted_at', 'status', 'deadline_status', 'id' ];
		$orderby        = in_array( $orderby, $allowed_orders, true ) ? $orderby : 'created_at';
		$order          = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';
		$offset         = max( 0, ( $paged - 1 ) * $per_page );

		// Dynamic WHERE/ORDER are built only from whitelisted columns ($allowed_orders)
		// and ASC/DESC; every value is bound through $wpdb->prepare(). The %s/%d
		// placeholders live inside $where_sql, so the placeholder-count sniffs cannot
		// see them and misfire.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$total = (int) $wpdb->get_var(
			empty( $params )
				? "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"
				: $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $params )
		);

		$list_params = array_merge( $params, [ $per_page, $offset ] );
		$results     = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $list_params )
		);
		// phpcs:enable

		return [
			'items' => array_map( [ WithdrawalCase::class, 'from_row' ], $results ),
			'total' => $total,
		];
	}

	/**
	 * Count cases grouped by status.
	 *
	 * @return array<string, int>
	 */
	public static function count_by_status(): array {
		global $wpdb;

		$table   = Schema::cases_table();
		$results = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

		$counts = [];
		foreach ( (array) $results as $row ) {
			$counts[ (string) $row['status'] ] = (int) $row['total'];
		}

		return $counts;
	}
}
