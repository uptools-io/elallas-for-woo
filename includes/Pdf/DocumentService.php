<?php
/**
 * Document service — generates and locates withdrawal-statement PDFs.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Pdf;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Database\CaseItemRepository;
use LightweightPlugins\Elallas\Database\DocumentRepository;
use LightweightPlugins\Elallas\Frontend\TemplateLoader;
use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * Creates durable-medium PDF documents for withdrawal cases.
 */
final class DocumentService {

	private const DOC_TYPE = 'withdrawal_statement';
	private const DIR       = 'elallas-docs/';

	/**
	 * Generate (and persist) a withdrawal-statement PDF for a case.
	 *
	 * @param int $case_id Case ID.
	 * @return int Document ID (0 on failure).
	 */
	public static function generate( int $case_id ): int {
		if ( ! Options::get( 'pdf_enabled' ) ) {
			return 0;
		}

		$case = CaseRepository::find( $case_id );

		if ( null === $case ) {
			return 0;
		}

		$items = CaseItemRepository::for_case( $case_id );
		$order = OrderAdapter::get_order( (int) $case->order_id );

		$html = TemplateLoader::render(
			'pdf/withdrawal-statement.php',
			[
				'case'  => $case,
				'items' => $items,
				'order' => $order,
			]
		);

		$pdf = PdfRenderer::to_pdf_string( $html, [ 'case_id' => $case_id ] );

		if ( '' === $pdf ) {
			return 0;
		}

		return self::persist( $case_id, (string) $case->case_number, $pdf );
	}

	/**
	 * Write the PDF to the protected dir and insert a document row.
	 *
	 * @param int    $case_id     Case ID.
	 * @param string $case_number Case number (for the file name).
	 * @param string $pdf         PDF bytes.
	 * @return int Document ID (0 on failure).
	 */
	private static function persist( int $case_id, string $case_number, string $pdf ): int {
		$upload   = wp_upload_dir();
		$base_dir = trailingslashit( $upload['basedir'] ) . self::DIR;

		if ( ! wp_mkdir_p( $base_dir ) ) {
			return 0;
		}

		$file_name = 'statement-' . sanitize_file_name( $case_number ) . '.pdf';
		$abs_path  = $base_dir . $file_name;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $abs_path, $pdf ) ) {
			return 0;
		}

		return DocumentRepository::insert(
			[
				'case_id'       => $case_id,
				'document_type' => self::DOC_TYPE,
				'file_path'     => self::DIR . $file_name,
				'file_hash'     => hash( 'sha256', $pdf ),
			]
		);
	}

	/**
	 * Resolve a document's absolute file path.
	 *
	 * @param int $document_id Document ID.
	 * @return string Absolute path, or '' if missing.
	 */
	public static function get_file_path( int $document_id ): string {
		$doc = DocumentRepository::find( $document_id );

		if ( null === $doc || empty( $doc->file_path ) ) {
			return '';
		}

		$upload = wp_upload_dir();
		$path   = trailingslashit( $upload['basedir'] ) . ltrim( (string) $doc->file_path, '/' );

		return file_exists( $path ) ? $path : '';
	}

	/**
	 * Build a token-gated download URL for a document.
	 *
	 * @param int $document_id Document ID.
	 * @return string
	 */
	public static function download_url( int $document_id ): string {
		return add_query_arg(
			[
				'elallas_doc' => $document_id,
				'token'       => self::token( $document_id ),
			],
			home_url( '/' )
		);
	}

	/**
	 * Compute the HMAC token for a document download URL.
	 *
	 * @param int $document_id Document ID.
	 * @return string
	 */
	public static function token( int $document_id ): string {
		return hash_hmac( 'sha256', (string) $document_id, wp_salt( 'auth' ) );
	}
}
