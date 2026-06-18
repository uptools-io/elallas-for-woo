<?php
/**
 * HTML → PDF renderer (Dompdf wrapper).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Pdf;

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
		if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
			return '';
		}

		/**
		 * Filter the HTML document just before it is rendered to PDF.
		 *
		 * @param string               $html    HTML document.
		 * @param array<string, mixed> $context Rendering context.
		 */
		$html = (string) apply_filters( 'elallas_pdf_html', $html, $context );

		$dompdf = new \Dompdf\Dompdf();
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( 'A4' );
		$dompdf->render();

		return (string) $dompdf->output();
	}
}
