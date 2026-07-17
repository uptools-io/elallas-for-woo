<?php
/**
 * Document repository.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Database;

/**
 * Database operations for generated documents.
 */
final class DocumentRepository {

	/**
	 * Insert a document record.
	 *
	 * @param array<string, mixed> $data Document data (case_id, document_type, file_path, file_hash).
	 * @return int Inserted ID (0 on failure).
	 */
	public static function insert( array $data ): int {
		global $wpdb;

		$data['created_at'] = $data['created_at'] ?? current_time( 'mysql' );

		$result = $wpdb->insert( Schema::documents_table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Find a document by ID.
	 *
	 * @param int $id Document ID.
	 * @return object|null
	 */
	public static function find( int $id ): ?object {
		global $wpdb;

		$table = Schema::documents_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query on the plugin's custom table; results are not object-cached.
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);

		return $row ? $row : null;
	}

	/**
	 * Get documents for a case.
	 *
	 * @param int $case_id Case ID.
	 * @return array<int, object>
	 */
	public static function for_case( int $case_id ): array {
		global $wpdb;

		$table = Schema::documents_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query on the plugin's custom table; results are not object-cached.
		return (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE case_id = %d ORDER BY created_at DESC", $case_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);
	}
}
