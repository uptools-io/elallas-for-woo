<?php
/**
 * Front-end template loader with theme override support.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

/**
 * Locates and renders front-end templates.
 *
 * Themes may override any template by placing it in
 * `wp-content/themes/<theme>/elallas-for-woo/<path>`.
 */
final class TemplateLoader {

	/**
	 * Render a template to a string.
	 *
	 * @param string               $template Relative path under templates/ (e.g. "frontend/confirm.php").
	 * @param array<string, mixed> $vars     Variables exposed to the template.
	 * @return string
	 */
	public static function render( string $template, array $vars = [] ): string {
		$file = self::locate( $template );

		if ( '' === $file ) {
			return '';
		}

		ob_start();
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		include $file;

		return (string) ob_get_clean();
	}

	/**
	 * Locate a template, preferring a theme override.
	 *
	 * @param string $template Relative path under templates/.
	 * @return string Absolute path or empty string.
	 */
	public static function locate( string $template ): string {
		$template = ltrim( $template, '/' );
		$override = locate_template( [ 'elallas-for-woo/' . $template ] );

		if ( '' !== $override ) {
			return $override;
		}

		$path = ELALLAS_FOR_WOO_PATH . 'templates/' . $template;

		return file_exists( $path ) ? $path : '';
	}
}
