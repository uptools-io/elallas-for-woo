<?php
/**
 * Builds the privacy-aware submission context for a case.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Security\Encryption;
use LightweightPlugins\Elallas\Security\RateLimiter;
use LightweightPlugins\Elallas\Integrations\Multilingual;

/**
 * Produces the context array consumed by CaseService::create().
 *
 * Honours the privacy settings for IP / user-agent / email storage.
 */
final class SubmissionContext {

	/**
	 * Build the context array.
	 *
	 * @param string $email           Customer email.
	 * @param string $withdrawal_type full|partial.
	 * @param string $deadline_status DeadlineStatus constant.
	 * @param string $note            Optional customer note.
	 * @param string $bank_account    Optional bank account number for the refund.
	 * @return array<string, mixed>
	 */
	public static function build( string $email, string $withdrawal_type, string $deadline_status, string $note = '', string $bank_account = '' ): array {
		return [
			'email_hash'             => Encryption::hash( $email ),
			'email_encrypted'        => Options::get( 'encrypt_email' ) ? Encryption::encrypt( $email ) : null,
			'bank_account_encrypted' => '' !== $bank_account ? Encryption::encrypt( $bank_account ) : null,
			'ip_hash'                => self::process( RateLimiter::client_ip(), (string) Options::get( 'store_ip', 'hash' ) ),
			'user_agent_hash'        => self::process( self::user_agent(), (string) Options::get( 'store_user_agent', 'hash' ) ),
			'source_url'             => self::current_url(),
			// Store the WPML/Polylang language code (falls back to the locale) so
			// emails and PDFs can later be rendered in the submission language.
			'language'               => Multilingual::current_language(),
			'withdrawal_type'        => $withdrawal_type,
			'deadline_status'        => $deadline_status,
			'customer_note'          => '' !== $note ? $note : null,
		];
	}

	/**
	 * Apply the configured storage mode to a value.
	 *
	 * @param string $value Raw value.
	 * @param string $mode  full|hash|off.
	 * @return string
	 */
	private static function process( string $value, string $mode ): string {
		return match ( $mode ) {
			'full'  => $value,
			'hash'  => '' !== $value ? Encryption::hash( $value ) : '',
			default => '',
		};
	}

	/**
	 * Current user agent.
	 *
	 * @return string
	 */
	private static function user_agent(): string {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}

	/**
	 * Current request URL.
	 *
	 * @return string
	 */
	private static function current_url(): string {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri );
	}
}
