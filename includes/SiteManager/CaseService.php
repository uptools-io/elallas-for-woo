<?php
/**
 * Execute callbacks for elállás case abilities.
 *
 * Holds the runtime logic invoked by CaseAbilities so the ability registration
 * class stays focused on schema/definition. All methods are static so they can
 * be referenced as ability execute_callback arrays.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\SiteManager;

use LightweightPlugins\Elallas\Database\CaseItemRepository;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Database\EventRepository;
use LightweightPlugins\Elallas\Domain\CaseService as DomainCaseService;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Models\WithdrawalCase;

/**
 * Executes elállás case abilities for the Site Manager.
 */
final class CaseService {

	/**
	 * List cases, optionally filtered by status and paged.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>
	 */
	public static function list_cases( array $input ): array {
		$filters = [];

		if ( ! empty( $input['status'] ) ) {
			$filters['status'] = sanitize_key( (string) $input['status'] );
		}

		$paged  = max( 1, (int) ( $input['paged'] ?? 1 ) );
		$result = CaseRepository::query( $filters, $paged );

		return [
			'success' => true,
			'cases'   => array_map( [ self::class, 'format_case' ], $result['items'] ),
			'total'   => (int) $result['total'],
		];
	}

	/**
	 * Get a case with its items and audit events.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_case( array $input ): array|\WP_Error {
		$case = CaseRepository::find( (int) ( $input['case_id'] ?? 0 ) );

		if ( ! $case ) {
			return new \WP_Error( 'not_found', __( 'Az ügy nem található.', 'elallas-for-woo' ), [ 'status' => 404 ] );
		}

		return [
			'success' => true,
			'case'    => self::format_case( $case ),
			'items'   => array_map( [ self::class, 'to_array' ], CaseItemRepository::for_case( $case->id ) ),
			'events'  => array_map( [ self::class, 'to_array' ], EventRepository::for_case( $case->id ) ),
		];
	}

	/**
	 * Change a case status.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function update_status( array $input ): array|\WP_Error {
		$case_id = (int) ( $input['case_id'] ?? 0 );
		$status  = sanitize_key( (string) ( $input['status'] ?? '' ) );
		$message = sanitize_textarea_field( (string) ( $input['message'] ?? '' ) );

		if ( ! CaseStatus::is_valid( $status ) ) {
			return new \WP_Error( 'invalid_status', __( 'Érvénytelen státuszérték.', 'elallas-for-woo' ), [ 'status' => 400 ] );
		}

		$changed = ( new DomainCaseService() )->change_status( $case_id, $status, get_current_user_id(), $message );

		if ( ! $changed ) {
			return new \WP_Error( 'update_failed', __( 'Az ügy státusza nem frissíthető.', 'elallas-for-woo' ), [ 'status' => 400 ] );
		}

		return [
			'success' => true,
			'message' => __( 'Az ügy státusza frissítve.', 'elallas-for-woo' ),
		];
	}

	/**
	 * Get the audit log for a case.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>
	 */
	public static function get_audit_log( array $input ): array {
		$events = EventRepository::for_case( (int) ( $input['case_id'] ?? 0 ) );

		return [
			'success' => true,
			'events'  => array_map( [ self::class, 'to_array' ], $events ),
		];
	}

	/**
	 * Format a case model for API output.
	 *
	 * @param WithdrawalCase $case Case model.
	 * @return array<string, mixed>
	 */
	private static function format_case( WithdrawalCase $case ): array {
		return [
			'id'              => $case->id,
			'case_number'     => $case->case_number,
			'order_id'        => $case->order_id,
			'order_number'    => $case->order_number,
			'customer_id'     => $case->customer_id,
			'status'          => $case->status,
			'status_label'    => $case->status_label(),
			'withdrawal_type' => $case->withdrawal_type,
			'deadline_status' => $case->deadline_status,
			'deadline_label'  => $case->deadline_label(),
			'submitted_at'    => $case->submitted_at,
			'confirmed_at'    => $case->confirmed_at,
			'created_at'      => $case->created_at,
			'updated_at'      => $case->updated_at,
		];
	}

	/**
	 * Cast a repository row object to a plain array.
	 *
	 * @param object|array<string, mixed> $row Row to cast.
	 * @return array<string, mixed>
	 */
	private static function to_array( object|array $row ): array {
		return (array) $row;
	}
}
