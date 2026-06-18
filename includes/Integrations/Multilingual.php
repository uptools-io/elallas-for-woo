<?php
/**
 * Multilingual integration.
 *
 * Registers the legal/UI strings with WPML, Polylang or TranslatePress so they
 * can be translated. All calls are guarded; absent any plugin it does nothing.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Integrations;

use LightweightPlugins\Elallas\Options;

/**
 * Exposes elállás legal strings to multilingual plugins.
 */
final class Multilingual {

	/**
	 * Translatable string context/group name.
	 */
	private const CONTEXT = 'elallas-for-woo';

	/**
	 * Option keys whose values are user-facing strings.
	 *
	 * @var array<int, string>
	 */
	private const STRING_KEYS = [
		'legal_declaration',
		'legal_confirmation',
		'button_label',
		'confirm_label',
	];

	/**
	 * Register hooks when a supported multilingual plugin is active.
	 */
	public function __construct() {
		if ( ! $this->has_plugin() ) {
			return;
		}

		add_action( 'init', [ $this, 'register_strings' ], 20 );
	}

	/**
	 * Whether any supported multilingual plugin is present.
	 *
	 * @return bool
	 */
	private function has_plugin(): bool {
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

			$this->register_string( $key, $value );
		}
	}

	/**
	 * Register a single string with whichever plugin is active.
	 *
	 * @param string $name  String name/identifier.
	 * @param string $value String value.
	 * @return void
	 */
	private function register_string( string $name, string $value ): void {
		if ( function_exists( 'pll_register_string' ) ) {
			pll_register_string( $name, $value, self::CONTEXT, true );
		}

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			do_action( 'wpml_register_single_string', self::CONTEXT, $name, $value );
		}
	}

	/**
	 * Opportunistically translate a value through an active plugin.
	 *
	 * @param string $value Source string.
	 * @param string $name  Registered string name.
	 * @return string Translated value, or the original when none is available.
	 */
	public function translate( string $value, string $name ): string {
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
}
