<?php
/**
 * Honeypot spam field.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Security;

/**
 * Renders a hidden honeypot field and detects bot submissions.
 */
final class Honeypot {

	/**
	 * Honeypot field name.
	 */
	public const FIELD = 'elallas_hp_email';

	/**
	 * Render the honeypot field markup.
	 *
	 * @return string
	 */
	public static function field(): string {
		return sprintf(
			'<div style="position:absolute;left:-9999px;" aria-hidden="true"><label>%1$s<input type="text" name="%2$s" value="" tabindex="-1" autocomplete="off" /></label></div>',
			esc_html__( 'Leave this field empty', 'elallas-for-woo' ),
			esc_attr( self::FIELD )
		);
	}

	/**
	 * Whether the submission looks like a bot (honeypot filled).
	 *
	 * @param array<string, mixed> $post Raw POST data.
	 * @return bool
	 */
	public static function is_bot( array $post ): bool {
		return ! empty( $post[ self::FIELD ] );
	}
}
