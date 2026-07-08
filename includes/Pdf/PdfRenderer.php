<?php
/**
 * HTML → PDF renderer (Dompdf wrapper).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Pdf;

use LightweightPlugins\Elallas\Support\Logger;

/**
 * Renders an HTML string to a PDF binary string via Dompdf.
 */
final class PdfRenderer {

	/**
	 * Convert an HTML document to a PDF binary string.
	 *
	 * @param string               $html    Standalone HTML document.
	 * @param array<string, mixed> $context Optional rendering context (passed to the filter).
	 * @return string PDF bytes, or '' if Dompdf is unavailable.
	 */
	public static function to_pdf_string( string $html, array $context = [] ): string {
		$dompdf_class = self::dompdf_class();

		if ( '' === $dompdf_class ) {
			Logger::error( 'PDF renderelés kihagyva: a Dompdf nem érhető el.', $context );
			return '';
		}

		/**
		 * Filter the HTML document just before it is rendered to PDF.
		 *
		 * @param string               $html    HTML document.
		 * @param array<string, mixed> $context Rendering context.
		 */
		$html = (string) apply_filters( 'elallas_pdf_html', $html, $context );

		try {
			$dompdf = new $dompdf_class();
			$dompdf->loadHtml( $html );
			$dompdf->setPaper( 'A4' );
			$dompdf->render();

			return (string) $dompdf->output();
		} catch ( \Throwable $e ) {
			// A PDF failure must never break case creation or e-mail sending.
			Logger::error(
				'PDF renderelési hiba: ' . $e->getMessage(),
				array_merge( $context, [ 'exception' => get_class( $e ) ] )
			);

			return '';
		}
	}

	/**
	 * Resolve the Dompdf class name.
	 *
	 * Prefers the scoped (Strauss-prefixed) class shipped in release builds so the
	 * bundled Dompdf never collides with another plugin's. Falls back to the global
	 * \Dompdf\Dompdf when installed as a Composer dependency.
	 *
	 * @return string Fully-qualified class name, or '' if Dompdf is unavailable.
	 */
	private static function dompdf_class(): string {
		$scoped = '\LightweightPlugins\Elallas\Vendor\Dompdf\Dompdf';

		if ( class_exists( $scoped ) ) {
			return $scoped;
		}

		return class_exists( '\Dompdf\Dompdf' ) ? '\Dompdf\Dompdf' : '';
	}
}
