<?php
/**
 * Document REST controller.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Api;

use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Database\DocumentRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Returns a token-gated download URL for a case's withdrawal-statement PDF.
 *
 * Guards on the optional PDF module: if it is not present the endpoint 404s.
 */
final class DocumentController {

	/**
	 * Fully-qualified PDF document service class.
	 *
	 * @var string
	 */
	private const PDF_SERVICE = '\\LightweightPlugins\\Elallas\\Pdf\\DocumentService';

	/**
	 * Admin permission check.
	 *
	 * @return bool
	 */
	public function permission_admin(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get the download URL for a case's document.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get( WP_REST_Request $request ) {
		if ( ! class_exists( self::PDF_SERVICE ) ) {
			return $this->not_found();
		}

		$case = CaseRepository::find( (int) $request['id'] );

		if ( null === $case ) {
			return $this->not_found();
		}

		$documents = DocumentRepository::for_case( $case->id );

		if ( empty( $documents ) ) {
			return $this->not_found();
		}

		$document = reset( $documents );
		$url      = ( self::PDF_SERVICE )::download_url( (int) $document->id );

		return new WP_REST_Response(
			[
				'case_id'      => $case->id,
				'document_id'  => (int) $document->id,
				'download_url' => esc_url_raw( $url ),
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
			'elallas_document_not_found',
			__( 'A dokumentum nem található.', 'elallas-for-woo' ),
			[ 'status' => 404 ]
		);
	}
}
