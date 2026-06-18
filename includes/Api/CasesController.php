<?php
/**
 * Cases REST controller.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Api;

use LightweightPlugins\Elallas\Database\CaseItemRepository;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Domain\CaseService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles REST endpoints for withdrawal cases.
 *
 * Create/confirm are delegated to CaseWriteHandler so the REST flow mirrors
 * the front-end form exactly.
 */
final class CasesController {

	use CaseResponseTrait;

	/**
	 * Admin permission check.
	 *
	 * @return bool
	 */
	public function permission_admin(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Public-but-validated permission check (REST nonce).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function permission_nonce( WP_REST_Request $request ): bool {
		$nonce = (string) $request->get_header( 'X-WP-Nonce' );

		return false !== wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Create (and confirm) a withdrawal case.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response {
		return ( new CaseWriteHandler() )->create( $request );
	}

	/**
	 * Confirm a case (public-but-validated re-check).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function confirm( WP_REST_Request $request ): WP_REST_Response {
		return ( new CaseWriteHandler() )->confirm( $request );
	}

	/**
	 * Get a single case (admin).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get( WP_REST_Request $request ) {
		$case = CaseRepository::find( (int) $request['id'] );

		if ( null === $case ) {
			return $this->not_found();
		}

		$items = CaseItemRepository::for_case( $case->id );

		return new WP_REST_Response( $this->prepare_case( $case, $items ), 200 );
	}

	/**
	 * Change a case status (admin).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function change_status( WP_REST_Request $request ) {
		$case = CaseRepository::find( (int) $request['id'] );

		if ( null === $case ) {
			return $this->not_found();
		}

		$status  = sanitize_key( (string) $request->get_param( 'status' ) );
		$changed = ( new CaseService() )->change_status( $case->id, $status, get_current_user_id() );

		if ( ! $changed ) {
			return new WP_Error(
				'elallas_status_unchanged',
				__( 'A státusz nem módosítható (érvénytelen vagy azonos állapot).', 'elallas-for-woo' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'case_id' => $case->id,
				'status'  => $status,
			],
			200
		);
	}

	/**
	 * Standard not-found error.
	 *
	 * @return WP_Error
	 */
	private function not_found(): WP_Error {
		return new WP_Error(
			'elallas_case_not_found',
			__( 'Az elállási ügy nem található.', 'elallas-for-woo' ),
			[ 'status' => 404 ]
		);
	}
}
