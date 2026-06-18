<?php
/**
 * Token-gated PDF download handler.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Pdf;

/**
 * Streams generated documents over a direct, token-gated URL.
 *
 * Files live in a protected upload sub-directory (deny-all .htaccess), so the
 * only way to reach them is this handler. Access requires either a valid HMAC
 * token or the `manage_woocommerce` capability. Path traversal is blocked.
 */
final class DownloadHandler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'maybe_download' ] );
	}

	/**
	 * Detect a download request and stream the file, or 403.
	 *
	 * @return void
	 */
	public function maybe_download(): void {
		if ( ! isset( $_GET['elallas_doc'] ) ) {
			return;
		}

		$document_id = absint( wp_unslash( $_GET['elallas_doc'] ) );
		$token       = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( ! $this->is_authorised( $document_id, $token ) ) {
			$this->forbidden();
		}

		$path = DocumentService::get_file_path( $document_id );

		if ( '' === $path || ! $this->is_within_docs_dir( $path ) ) {
			$this->forbidden();
		}

		$this->stream( $path );
	}

	/**
	 * Whether the request may access the document.
	 *
	 * @param int    $document_id Document ID.
	 * @param string $token       Supplied token.
	 * @return bool
	 */
	private function is_authorised( int $document_id, string $token ): bool {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return $document_id > 0 && hash_equals( DocumentService::token( $document_id ), $token );
	}

	/**
	 * Whether a resolved path sits inside the protected docs directory.
	 *
	 * @param string $path Absolute file path.
	 * @return bool
	 */
	private function is_within_docs_dir( string $path ): bool {
		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . 'elallas-docs/';
		$real   = realpath( $path );
		$root   = realpath( $dir );

		if ( false === $real || false === $root ) {
			return false;
		}

		return 0 === strpos( $real, trailingslashit( $root ) );
	}

	/**
	 * Stream the file as an attachment and exit.
	 *
	 * @param string $path Absolute file path.
	 * @return void
	 */
	private function stream( string $path ): void {
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );
		exit;
	}

	/**
	 * Emit a 403 and stop.
	 *
	 * @return void
	 */
	private function forbidden(): void {
		wp_die(
			esc_html__( 'A dokumentum nem érhető el.', 'elallas-for-woo' ),
			esc_html__( 'Hozzáférés megtagadva', 'elallas-for-woo' ),
			[ 'response' => 403 ]
		);
	}
}
