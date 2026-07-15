<?php
/**
 * Options management class.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas;

use LightweightPlugins\Elallas\Data\DefaultTexts;

/**
 * Handles plugin options and post/order meta.
 */
final class Options {

	/**
	 * Option name in database.
	 */
	public const OPTION_NAME = 'lw_elallas_options';

	/**
	 * Meta key prefix.
	 */
	public const META_PREFIX = '_lw_elallas_';

	/**
	 * Cached options.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $options = null;

	/**
	 * Get default options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		// Note: user-facing string defaults are intentionally raw (no __()) so
		// this can run before init/textdomain load (Options::get() is called
		// from module constructors on plugins_loaded). These values are the
		// Hungarian source strings; translation happens at output time through
		// the multilingual string mechanism (see Integrations\Multilingual).
		return [
			// General.
			'enabled'                 => true,
			'withdrawal_page_id'      => 0,
			'button_label'            => 'Elállás a szerződéstől',
			'confirm_label'           => 'Elállás megerősítése',
			'display_account'         => true,
			'display_order_details'   => true,
			'display_order_email'     => true,

			// Deadline.
			'deadline_days'           => 14,
			'deadline_start'          => 'order_completed', // order_created|order_completed|delivery|manual.
			'expired_handling'        => 'allow_with_warning', // allow_with_warning|block|require_approval.

			// Order statuses eligible for withdrawal.
			'eligible_statuses'       => [ 'processing', 'completed' ],
			'use_wc_statuses'         => false,

			// Privacy.
			'store_ip'                => 'hash', // full|hash|off.
			'store_user_agent'        => 'hash', // full|hash|off.
			'encrypt_email'           => true,
			'retention_days'          => 0, // 0 = keep forever.

			// Documents.
			'pdf_enabled'             => true,

			// Emails.
			'email_customer_enabled'  => true,
			'email_admin_enabled'     => true,
			'email_status_enabled'    => true,
			'email_admin_recipient'   => '',
			'email_from_name'         => '',
			'email_from_address'      => '',
			'email_customer_extra'    => '',
			'email_order_text'        => 'Ha elektronikusan szeretne elállni a vásárlástól, azt az alábbi oldalon teheti meg: {link}',
			'email_policy_enabled'    => false,
			'email_policy_url'        => '',
			'email_policy_label'      => 'Általános Szerződési Feltételek (ÁSZF)',

			// Legal texts.
			'legal_declaration'       => DefaultTexts::declaration(),
			'legal_confirmation'      => DefaultTexts::confirmation(),

			// Diagnostics.
			'logging_enabled'         => false,

			// Uninstall.
			'uninstall_remove_data'   => false,
		];
	}

	/**
	 * Get all options merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		if ( null === self::$options ) {
			$saved         = get_option( self::OPTION_NAME, [] );
			self::$options = wp_parse_args( is_array( $saved ) ? $saved : [], self::get_defaults() );
		}

		return self::$options;
	}

	/**
	 * Get a single option.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$options = self::get_all();

		if ( array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}

		return $default ?? ( self::get_defaults()[ $key ] ?? null );
	}

	/**
	 * Set a single option.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	public static function set( string $key, mixed $value ): bool {
		$options         = self::get_all();
		$options[ $key ] = $value;

		return self::save( $options );
	}

	/**
	 * Save all options.
	 *
	 * @param array<string, mixed> $options Options.
	 * @return bool
	 */
	public static function save( array $options ): bool {
		self::$options = $options;
		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Normalize a free-form recipient string into a validated, comma-separated list.
	 *
	 * Accepts commas, semicolons, spaces and newlines as separators, so merchants
	 * can paste addresses in any common format (WooCommerce itself only understands
	 * comma-separated recipients — a semicolon- or space-separated value would
	 * otherwise silently fail to reach anyone). Invalid addresses are dropped and
	 * duplicates are removed (case-insensitively).
	 *
	 * @param string $raw Raw recipient string.
	 * @return string Comma-separated list of valid e-mail addresses ('' if none).
	 */
	public static function sanitize_email_list( string $raw ): string {
		$parts = preg_split( '/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );

		if ( ! is_array( $parts ) ) {
			return '';
		}

		$valid = [];

		foreach ( $parts as $part ) {
			$email = sanitize_email( $part );

			if ( '' !== $email && is_email( $email ) ) {
				$valid[ strtolower( $email ) ] = $email;
			}
		}

		return implode( ', ', array_values( $valid ) );
	}

	/**
	 * Get order meta value (without prefix).
	 *
	 * @param int    $order_id Order ID.
	 * @param string $key      Meta key without prefix.
	 * @param mixed  $default  Default value.
	 * @return mixed
	 */
	public static function get_order_meta( int $order_id, string $key, mixed $default = '' ): mixed {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;

		if ( ! $order ) {
			return $default;
		}

		$value = $order->get_meta( self::META_PREFIX . $key );

		return '' !== $value ? $value : $default;
	}

	/**
	 * Clear options cache.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$options = null;
	}
}
