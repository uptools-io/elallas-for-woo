<?php
/**
 * Bulk-action processor for the cases list table.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Domain\CaseService;
use LightweightPlugins\Elallas\Models\CaseStatus;

/**
 * Applies bulk status changes / deletions to selected cases.
 */
final class CaseBulkActions {

	/**
	 * Map bulk action keys to target statuses.
	 *
	 * @return array<string, string>
	 */
	private static function map(): array {
		return [
			'mark_review'   => CaseStatus::MANUAL_REVIEW,
			'mark_accepted' => CaseStatus::ACCEPTED,
			'mark_rejected' => CaseStatus::REJECTED,
			'mark_closed'   => CaseStatus::CLOSED,
		];
	}

	/**
	 * Process a bulk action against the selected case IDs.
	 *
	 * @param string $action       Current bulk action.
	 * @param string $nonce_action Nonce action to verify.
	 * @return void
	 */
	public static function process( string $action, string $nonce_action ): void {
		if ( '' === $action ) {
			return;
		}

		check_admin_referer( $nonce_action );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$ids = isset( $_REQUEST['case'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['case'] ) ) : [];
		$map = self::map();

		$service = new CaseService();
		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				CaseRepository::update( $id, [ 'status' => CaseStatus::CANCELLED ] );
			} elseif ( isset( $map[ $action ] ) ) {
				$service->change_status( $id, $map[ $action ], get_current_user_id() );
			}
		}
	}
}
