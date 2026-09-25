<?php
/**
 * Official EU notice / GARAN asset registry (pure).
 *
 * Knows the 24 official languages, the bundled (byte-identical) Commission
 * files and the QR-code target of every notice. Uses no WordPress function and
 * no plugin constant: paths are relative to the plugin root, callers prefix
 * them with ELALLAS_FOR_WOO_URL / ELALLAS_FOR_WOO_PATH.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * Pure lookups for the official compliance assets.
 */
final class OfficialAssets {

	/**
	 * Asset set version; part of generated-file hashes (GARAN PNG cache).
	 */
	public const ASSET_VERSION = '2025-10';

	/**
	 * Fallback language (the shop's target market).
	 */
	public const FALLBACK_CODE = 'hu';

	/**
	 * Language code => Commission file code (PDF package naming).
	 *
	 * @var array<string, string>
	 */
	public const LANGS = [
		'bg' => 'BGN',
		'cs' => 'CSN',
		'da' => 'DAN',
		'de' => 'DEN',
		'el' => 'ELN',
		'en' => 'ENN',
		'es' => 'ESN',
		'et' => 'ETN',
		'fi' => 'FIN',
		'fr' => 'FRN',
		'ga' => 'GAN',
		'hr' => 'HRN',
		'hu' => 'HUN',
		'it' => 'ITN',
		'lt' => 'LTN',
		'lv' => 'LVN',
		'mt' => 'MTN',
		'nl' => 'NLN',
		'pl' => 'PLN',
		'pt' => 'PTN',
		'ro' => 'RON',
		'sk' => 'SKN',
		'sl' => 'SLN',
		'sv' => 'SVN',
	];

	/**
	 * QR-code target of each official notice (decoded with bin/decode-notice-qr.py).
	 *
	 * The link next to the notice must point to the same destination as its QR code.
	 *
	 * @var array<string, string>
	 */
	public const LINKS = [
		'bg' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_bg.htm',
		'cs' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_cs.htm',
		'da' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_da.htm',
		'de' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_de.htm',
		'el' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_el.htm',
		'en' => 'https://europa.eu/youreurope/guarantees',
		'es' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_es.htm',
		'et' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_et.htm',
		'fi' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_fi.htm',
		'fr' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_fr.htm',
		'ga' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_ga.htm',
		'hr' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_hr.htm',
		'hu' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_hu.htm',
		'it' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_it.htm',
		'lt' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_lt.htm',
		'lv' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_lv.htm',
		'mt' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_mt.htm',
		'nl' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_nl.htm',
		'pl' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_pl.htm',
		'pt' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_pt.htm',
		'ro' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_ro.htm',
		'sk' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_sk.htm',
		'sl' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_sl.htm',
		'sv' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_sv.htm',
	];

	/**
	 * Short link text printed under the QR code (after LINK_LABEL_PREFIX).
	 *
	 * @var array<string, string>
	 */
	public const LINK_LABELS = [
		'bg' => 'гаранции',
		'cs' => 'záruky_cs',
		'da' => 'garantier',
		'de' => 'garantien',
		'el' => 'εγγυήσεις',
		'en' => 'guarantees',
		'es' => 'garantías',
		'et' => 'garantiid',
		'fi' => 'virhevastuu',
		'fr' => 'garanties',
		'ga' => 'ráthaíochtaí',
		'hr' => 'jamstva_hr',
		'hu' => 'jótállás',
		'it' => 'garanzie',
		'lt' => 'garantijos',
		'lv' => 'garantijas',
		'mt' => 'garanziji',
		'nl' => 'garantie',
		'pl' => 'gwarancje',
		'pt' => 'garantias',
		'ro' => 'garanții',
		'sk' => 'záruky_sk',
		'sl' => 'jamstva_sl',
		'sv' => 'reklamationsrätt',
	];

	/**
	 * Prefix of the visible short link text.
	 */
	public const LINK_LABEL_PREFIX = 'europa.eu/youreurope/';

	/**
	 * Languages without an official colour PNG: the Commission's
	 * "PNG and JPG.zip" has no English file, EN is published as SVG/PDF only.
	 *
	 * @var array<int, string>
	 */
	public const NO_PNG = [ 'en' ];

	/**
	 * Pixel size of every official colour notice PNG (A4 portrait).
	 *
	 * @var array{width: int, height: int}
	 */
	public const PNG_SIZE = [
		'width'  => 1654,
		'height' => 2339,
	];

	/**
	 * Intrinsic size of the official notice SVGs (viewBox, A4 in pt).
	 *
	 * @var array{width: float, height: float}
	 */
	public const SVG_SIZE = [
		'width'  => 595.28,
		'height' => 841.89,
	];

	/**
	 * GARAN variant => bundled SVG path (relative to the plugin root).
	 *
	 * @var array<string, string>
	 */
	public const GARAN_SVG = [
		'colour' => 'assets/garan/garan-label-colour.svg',
		'nested' => 'assets/garan/garan-label-nested.svg',
	];

	/**
	 * Expected sha256 of the bundled official GARAN SVGs (keyed by basename).
	 *
	 * @var array<string, string>
	 */
	public const SVG_SHA256 = [
		'garan-label-colour.svg' => '3414c8366823db39702d2557e614f882b530487ce589e903afdc98b7225e1ab1',
		'garan-label-nested.svg' => '1c1122b25f39333b329e4156b572b65f1f850592df0b061358b9404f43acb994',
	];

	/**
	 * All supported language codes.
	 *
	 * @return array<int, string>
	 */
	public static function codes(): array {
		return array_keys( self::LANGS );
	}

	/**
	 * Whether a two-letter code is one of the 24 official languages.
	 *
	 * @param string $code Language code.
	 * @return bool
	 */
	public static function is_supported( string $code ): bool {
		return isset( self::LANGS[ $code ] );
	}

	/**
	 * Normalise a language/locale string to its lower-case primary subtag
	 * (`hu_HU` → `hu`, `en-gb` → `en`, `pt-pt` → `pt`).
	 *
	 * @param string $raw Language code or locale.
	 * @return string
	 */
	public static function normalize( string $raw ): string {
		$parts = preg_split( '/[_-]/', strtolower( trim( $raw ) ) );

		return is_array( $parts ) ? (string) $parts[0] : '';
	}

	/**
	 * Resolve the notice language: current language, then the site default,
	 * then the locale, finally Hungarian.
	 *
	 * @param string $current Current language (WPML code / Polylang slug / locale).
	 * @param string $fallback Default site language.
	 * @param string $locale  WordPress locale.
	 * @return string One of codes().
	 */
	public static function resolve_code( string $current, string $fallback = '', string $locale = '' ): string {
		foreach ( [ $current, $fallback, $locale ] as $candidate ) {
			$code = self::normalize( $candidate );
			if ( self::is_supported( $code ) ) {
				return $code;
			}
		}

		return self::FALLBACK_CODE;
	}

	/**
	 * Whether an official colour PNG exists for the language.
	 *
	 * @param string $code Supported language code.
	 * @return bool
	 */
	public static function has_png( string $code ): bool {
		return self::is_supported( $code ) && ! in_array( $code, self::NO_PNG, true );
	}

	/**
	 * Relative path of the official colour PNG, or '' when none is published.
	 *
	 * @param string $code Supported language code.
	 * @return string
	 */
	public static function png_relpath( string $code ): string {
		return self::has_png( $code ) ? 'assets/notice/notice-' . $code . '.png' : '';
	}

	/**
	 * Relative path of the official colour SVG (text is outlined, safe in <img>).
	 *
	 * @param string $code Supported language code.
	 * @return string '' for an unsupported code.
	 */
	public static function svg_relpath( string $code ): string {
		return self::is_supported( $code ) ? 'assets/notice/notice-' . $code . '.svg' : '';
	}

	/**
	 * QR-code target URL for the language (Hungarian for unsupported codes).
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function link_url( string $code ): string {
		return self::LINKS[ $code ] ?? self::LINKS[ self::FALLBACK_CODE ];
	}

	/**
	 * Visible short link text, e.g. `europa.eu/youreurope/jótállás`.
	 *
	 * @param string $code Language code.
	 * @return string
	 */
	public static function link_label( string $code ): string {
		return self::LINK_LABEL_PREFIX . ( self::LINK_LABELS[ $code ] ?? self::LINK_LABELS[ self::FALLBACK_CODE ] );
	}

	/**
	 * Relative path of a bundled GARAN SVG.
	 *
	 * @param string $variant `colour` or `nested`.
	 * @return string '' for an unknown variant.
	 */
	public static function garan_svg_relpath( string $variant ): string {
		return self::GARAN_SVG[ $variant ] ?? '';
	}
}
