<?php
/**
 * Language, official file URLs and link of the harmonised notice.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Integrations\Multilingual;

/**
 * Resolves which official notice to show and where it lives.
 *
 * Web: the official outlined SVG (every language, vector, legible when zoomed).
 * E-mail: the official colour PNG where the Commission publishes one
 * (`OfficialAssets::has_png()`); otherwise text + link only, never a self-made image.
 */
final class NoticeSource {

	/**
	 * Notice language for the current request.
	 *
	 * @param string $context Output context (product, checkout, email, …).
	 * @param string $current Explicit current language ('' = detect).
	 * @return string One of OfficialAssets::codes().
	 */
	public static function language( string $context, string $current = '' ): string {
		$current = '' !== $current ? $current : Multilingual::current_language();
		$locale  = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$code    = OfficialAssets::resolve_code( $current, Multilingual::default_language(), $locale );

		/**
		 * Filter the notice language (two-letter code of one of the 24 official languages).
		 *
		 * @param string $code    Language code.
		 * @param string $context Output context.
		 */
		$filtered = apply_filters( 'elallas_notice_language', $code, $context );

		return is_string( $filtered ) && OfficialAssets::is_supported( $filtered ) ? $filtered : $code;
	}

	/**
	 * Web image URL (official SVG).
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function image_url( string $code ): string {
		return self::filtered_image( ELALLAS_FOR_WOO_URL . OfficialAssets::svg_relpath( $code ), $code );
	}

	/**
	 * E-mail image URL (official colour PNG), '' when none is published.
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function png_url( string $code ): string {
		$rel = OfficialAssets::png_relpath( $code );

		return '' === $rel ? '' : self::filtered_image( ELALLAS_FOR_WOO_URL . $rel, $code );
	}

	/**
	 * Absolute path of the official colour PNG (attachments), '' when none exists.
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function png_path( string $code ): string {
		$rel = OfficialAssets::png_relpath( $code );

		return '' !== $rel && file_exists( ELALLAS_FOR_WOO_PATH . $rel ) ? ELALLAS_FOR_WOO_PATH . $rel : '';
	}

	/**
	 * QR-code target link (filterable within europa.eu only).
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function link_url( string $code ): string {
		$official = OfficialAssets::link_url( $code );

		/**
		 * Filter the notice link. Only https URLs on europa.eu (or a subdomain) are accepted.
		 *
		 * @param string $url  Official QR-code target.
		 * @param string $code Language code.
		 */
		return NoticeUrlPolicy::link( apply_filters( 'elallas_notice_link_url', $official, $code ), $official );
	}

	/**
	 * Visible short link text (e.g. europa.eu/youreurope/jótállás).
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function link_label( string $code ): string {
		return OfficialAssets::link_label( $code );
	}

	/**
	 * Alternative text of the notice image.
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function alt( string $code ): string {
		return __( 'Hivatalos EU-tájékoztató a jogszabályi szavatosságról', 'elallas-for-woo' ) . ' (' . $code . ')';
	}

	/**
	 * Apply the CDN-only image filter.
	 *
	 * @param string $official Official URL.
	 * @param string $code     Language code.
	 * @return string
	 */
	private static function filtered_image( string $official, string $code ): string {
		/**
		 * Filter the notice image URL (CDN / host rewrite only: the basename must stay
		 * `notice-<code>.svg` / `.png`, otherwise the official URL is used).
		 *
		 * @param string $url  Official file URL.
		 * @param string $code Language code.
		 */
		return NoticeUrlPolicy::image( apply_filters( 'elallas_notice_image_url', $official, $code ), $official );
	}
}
