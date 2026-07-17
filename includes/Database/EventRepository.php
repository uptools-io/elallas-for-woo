<?php
/**
 * Event (audit log) repository.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Database;

/**
 * Append-only audit log operations.
 */
final class EventRepository {

	/**
	 * Log an event.
	 *
	 * @param int                  $case_id    Case ID.
	 * @param string               $event_type Event type slug.
	 * @param string               $actor_type customer|admin|system.
	 * @param int|null             $actor_id   Actor user ID.
	 * @param string               $message    Human-readable message.
	 * @param array<string, mixed> $metadata   Extra metadata.
	 * @return int Inserted ID (0 on failure).
	 */
	public static function log( int $case_id, string $event_type, string $actor_type, ?int $actor_id, string $message, array $metadata = [] ): int {
		global $wpdb;

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			Schema::events_table(),
			[
				'case_id'       => $case_id,
				'event_type'    => $event_type,
				'actor_type'    => $actor_type,
				'actor_id'      => $actor_id ?? 0,
				'message'       => $message,
				'metadata_json' => empty( $metadata ) ? null : (string) wp_json_encode( $metadata ),
				'created_at'    => current_time( 'mysql' ),
			]
		);

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Get events for a case (chronological).
	 *
	 * @param int $case_id Case ID.
	 * @return array<int, object>
	 */
	public static function for_case( int $case_id ): array {
		global $wpdb;

		$table = Schema::events_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query on the plugin's custom table; results are not object-cached.
		return (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE case_id = %d ORDER BY created_at ASC, id ASC", $case_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		);
	}
}
