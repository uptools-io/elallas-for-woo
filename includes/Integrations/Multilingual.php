<?php
/**
 * Multilingual integration.
 *
 * Central compatibility layer for WPML, Polylang and TranslatePress. It does
 * three things:
 *
 *  1. Registers the plugin's admin-entered / option strings so String
 *     Translation (WPML) or Polylang can expose them to translators.
 *  2. Exposes a single {@see Multilingual::translate_option_string()} helper
 *     that every output path uses to print the translated value in the current
 *     language (falling back to the stored value when nothing is available).
 *  3. Provides language switching helpers so background render paths (emails,
 *     PDFs) can render in the case/order language and restore afterwards.
 *
 * All calls are guarded; with no multilingual plugin present the helpers return
 * the original value and switching is a no-op.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Integrations;

use LightweightPlugins\Elallas\Options;

/**
 * Exposes elállás dynamic strings to multilingual plugins and centralises
 * per-language rendering.
 */
final class Multilingual {

	/**
	 * Translatable string context/group name. Stable — changing it orphans
	 * existing WPML/Polylang translations.
	 */
	private const CONTEXT = 'elallas-for-woo';

	/**
	 * Option keys whose values are user-facing strings entered in the admin.
	 *
	 * @var array<int, string>
	 */
	private const STRING_KEYS = [
		'legal_declaration',
		'legal_confirmation',
		'button_label',
		'confirm_label',
		'email_customer_extra',
		'email_order_text',
		'email_policy_label',
	];

	/**
	 * Stack of previous language codes for nestable switch/restore.
	 *
	 * @var array<int, string|null>
	 */
	private static array $language_stack = [];

	/**
	 * Register hooks when a supported multilingual plugin is active.
	 */
	public function __construct() {
		if ( ! self::has_plugin() ) {
			return;
		}

		add_action( 'init', [ $this, 'register_strings' ], 20 );
	}

	/**
	 * Whether any supported multilingual plugin is present.
	 *
	 * @return bool
	 */
	public static function has_plugin(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' )
			|| function_exists( 'pll_register_string' )
			|| class_exists( '\TRP_Translate_Press' )
			|| defined( 'TRP_PLUGIN_VERSION' );
	}

	/**
	 * Register the legal/UI strings for translation.
	 *
	 * @return void
	 */
	public function register_strings(): void {
		foreach ( self::STRING_KEYS as $key ) {
			$value = (string) Options::get( $key, '' );

			if ( '' === $value ) {
				continue;
			}

			self::register_string( $key, $value );
		}
	}

	/**
	 * Register a single string with whichever plugin is active.
	 *
	 * @param string $name  String name/identifier.
	 * @param string $value String value.
	 * @return void
	 */
	private static function register_string( string $name, string $value ): void {
		if ( function_exists( 'pll_register_string' ) ) {
			pll_register_string( $name, $value, self::CONTEXT, true );
		}

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			// Handled by the WPML String Translation add-on; a silent no-op without it.
			do_action( 'wpml_register_single_string', self::CONTEXT, $name, $value );
		}
	}

	/**
	 * Translate a stored option string for output in the current language.
	 *
	 * This is the single entry point every output path should use. When no
	 * multilingual plugin is active (or the string is untranslated) it returns
	 * the stored/original value unchanged.
	 *
	 * @param string      $key   Option key (also the registered string name).
	 * @param string|null $value Explicit value to translate; when null the
	 *                           stored option value is used.
	 * @return string
	 */
	public static function translate_option_string( string $key, ?string $value = null ): string {
		if ( null === $value ) {
			$value = (string) Options::get( $key, '' );
		}

		if ( '' === $value ) {
			return '';
		}

		return self::translate_string( $value, $key );
	}

	/**
	 * Translate a raw string value registered under the given name.
	 *
	 * @param string $value Source string.
	 * @param string $name  Registered string name.
	 * @return string Translated value, or the original when none is available.
	 */
	public static function translate_string( string $value, string $name ): string {
		if ( function_exists( 'pll__' ) ) {
			$translated = pll__( $value );

			if ( is_string( $translated ) && '' !== $translated ) {
				return $translated;
			}
		}

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$translated = apply_filters( 'wpml_translate_single_string', $value, self::CONTEXT, $name );

			if ( is_string( $translated ) && '' !== $translated ) {
				return $translated;
			}
		}

		return $value;
	}

	/**
	 * Back-compat instance wrapper around {@see translate_string()}.
	 *
	 * @param string $value Source string.
	 * @param string $name  Registered string name.
	 * @return string
	 */
	public function translate( string $value, string $name ): string {
		return self::translate_string( $value, $name );
	}

	/**
	 * Current front-end language as a plugin-stored code.
	 *
	 * Prefers the WPML/Polylang 2-letter language code (so it can be fed back
	 * to {@see switch_to()}), and falls back to the WordPress locale.
	 *
	 * @return string
	 */
	public static function current_language(): string {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$code = apply_filters( 'wpml_current_language', '' );

			if ( is_string( $code ) && '' !== $code ) {
				return $code;
			}
		}

		if ( function_exists( 'pll_current_language' ) ) {
			$code = pll_current_language( 'slug' );

			if ( is_string( $code ) && '' !== $code ) {
				return $code;
			}
		}

		return function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	}

	/**
	 * The site's default (original) language code, or '' when unknown.
	 *
	 * @return string
	 */
	public static function default_language(): string {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$code = apply_filters( 'wpml_default_language', null );

			if ( is_string( $code ) && '' !== $code ) {
				return $code;
			}
		}

		if ( function_exists( 'pll_default_language' ) ) {
			$code = pll_default_language( 'slug' );

			if ( is_string( $code ) && '' !== $code ) {
				return $code;
			}
		}

		return '';
	}

	/**
	 * Resolve the translated ID of a post/term for the current language.
	 *
	 * @param int    $object_id Stored object ID.
	 * @param string $type      'post' | 'page' | CPT | taxonomy.
	 * @return int Translated ID, or the original when no translation exists.
	 */
	public static function object_id( int $object_id, string $type = 'page' ): int {
		if ( $object_id <= 0 ) {
			return $object_id;
		}

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$translated = apply_filters( 'wpml_object_id', $object_id, $type, true );

			if ( is_numeric( $translated ) ) {
				return (int) $translated;
			}
		}

		if ( function_exists( 'pll_get_post' ) && in_array( $type, [ 'post', 'page' ], true ) ) {
			$translated = pll_get_post( $object_id );

			if ( is_numeric( $translated ) && (int) $translated > 0 ) {
				return (int) $translated;
			}
		}

		return $object_id;
	}

	/**
	 * Resolve a post/term ID to its DEFAULT-language original.
	 *
	 * Used before reading configuration meta (e.g. withdrawal exclusions) so a
	 * setting made on the source product/term applies to every translation. WPML
	 * keeps this meta in sync via wpml-config.xml `copy`, but Polylang has no such
	 * mechanism — canonicalizing the ID on read makes the lookup language-agnostic
	 * under both without relying on custom-field synchronization.
	 *
	 * @param int    $object_id Stored object ID.
	 * @param string $type      Post type (e.g. 'product') or taxonomy ('product_cat'|'product_tag').
	 * @return int Default-language ID, or the original when it cannot be resolved.
	 */
	public static function default_object_id( int $object_id, string $type ): int {
		if ( $object_id <= 0 ) {
			return $object_id;
		}

		$default = self::default_language();

		if ( '' === $default ) {
			return $object_id;
		}

		$is_term = in_array( $type, [ 'product_cat', 'product_tag' ], true );

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			// WPML element type: the post type for posts, the taxonomy for terms.
			$translated = apply_filters( 'wpml_object_id', $object_id, $type, true, $default );

			if ( is_numeric( $translated ) && (int) $translated > 0 ) {
				return (int) $translated;
			}
		}

		if ( $is_term && function_exists( 'pll_get_term' ) ) {
			$translated = pll_get_term( $object_id, $default );
		} elseif ( ! $is_term && function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $object_id, $default );
		} else {
			$translated = 0;
		}

		return ( is_numeric( $translated ) && (int) $translated > 0 ) ? (int) $translated : $object_id;
	}

	/**
	 * Switch the active language for a background render (email, PDF).
	 *
	 * Captures the current language so the matching {@see restore()} returns to
	 * exactly where we were, allowing nested switches. A no-op (but still
	 * balanced) when WPML is absent or the code is empty.
	 *
	 * @param string $language Stored language code/locale.
	 * @return void
	 */
	public static function switch_to( string $language ): void {
		$code = self::normalize_language( $language );

		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || '' === $code ) {
			self::$language_stack[] = null; // Nothing switched; keep the stack balanced.
			return;
		}

		$current = apply_filters( 'wpml_current_language', '' );
		self::$language_stack[] = is_string( $current ) ? $current : null;

		do_action( 'wpml_switch_language', $code );
	}

	/**
	 * Restore the language captured by the matching {@see switch_to()} call.
	 *
	 * @return void
	 */
	public static function restore(): void {
		if ( empty( self::$language_stack ) ) {
			return;
		}

		$previous = array_pop( self::$language_stack );

		if ( null === $previous || '' === $previous || ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return;
		}

		do_action( 'wpml_switch_language', $previous );
	}

	/**
	 * Normalise a stored language value to a WPML language code.
	 *
	 * Handles legacy cases that stored a WordPress locale (e.g. "hu_HU") as
	 * well as new cases that store a 2-letter code (e.g. "hu").
	 *
	 * @param string $stored Stored value.
	 * @return string WPML language code, or the input when it cannot be mapped.
	 */
	public static function normalize_language( string $stored ): string {
		if ( '' === $stored ) {
			return '';
		}

		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return $stored;
		}

		$languages = apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] );

		if ( ! is_array( $languages ) || empty( $languages ) ) {
			return $stored;
		}

		// Already an active language code.
		if ( isset( $languages[ $stored ] ) ) {
			return $stored;
		}

		// Match on the full locale (e.g. hu_HU) exposed as default_locale.
		foreach ( $languages as $code => $details ) {
			if ( isset( $details['default_locale'] ) && $details['default_locale'] === $stored ) {
				return (string) $code;
			}
		}

		// Fall back to the locale prefix (hu_HU -> hu).
		$prefix = substr( $stored, 0, 2 );

		if ( isset( $languages[ $prefix ] ) ) {
			return $prefix;
		}

		return $stored;
	}
}
