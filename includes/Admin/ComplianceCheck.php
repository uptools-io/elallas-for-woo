<?php
/**
 * Configuration check behind the compliance admin notice (pure).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

/**
 * Decides whether the deadline / configuration warning is due and what it says.
 * Takes plain values (options array, flags, dates) so it is unit-testable.
 */
final class ComplianceCheck {

	/**
	 * Date from which the notice and the GARAN label are mandatory.
	 */
	public const DEADLINE = '2026-09-27';

	/**
	 * A dismissal hides the warning for this long.
	 */
	public const SNOOZE_SECONDS = 30 * 86400;

	public const NOTICE_OFF     = 'notice_off';
	public const NOTICE_HIDDEN  = 'notice_hidden';
	public const CHECKOUT_OFF   = 'checkout_off';
	public const GARAN_FILTERED = 'garan_filtered';
	public const NOT_REVIEWED   = 'not_reviewed';

	/**
	 * Notice placement option keys.
	 *
	 * @var array<int, string>
	 */
	private const DISPLAY_KEYS = [
		'notice_display_product',
		'notice_display_header',
		'notice_display_footer',
		'notice_display_checkout',
		'notice_display_email',
	];

	/**
	 * Problems found in the configuration (most severe first).
	 *
	 * @param array<string, mixed> $options                 Plugin options (merged with defaults).
	 * @param bool                 $garan_checkout_filtered Whether elallas_garan_checkout_visible has a callback.
	 * @return array<int, string>
	 */
	public static function problems( array $options, bool $garan_checkout_filtered ): array {
		$problems = [];

		if ( empty( $options['notice_enabled'] ) ) {
			$problems[] = self::NOTICE_OFF;
		} elseif ( ! array_filter( array_map( static fn( string $key ): bool => ! empty( $options[ $key ] ), self::DISPLAY_KEYS ) ) ) {
			$problems[] = self::NOTICE_HIDDEN;
		} elseif ( empty( $options['notice_display_checkout'] ) ) {
			$problems[] = self::CHECKOUT_OFF;
		}

		if ( $garan_checkout_filtered ) {
			$problems[] = self::GARAN_FILTERED;
		}

		if ( empty( $options['compliance_reviewed'] ) ) {
			$problems[] = self::NOT_REVIEWED;
		}

		return $problems;
	}

	/**
	 * Whether a dismissal (timestamp) is still in effect.
	 *
	 * @param int $dismissed_at Dismissal time (0 = never).
	 * @param int $now          Current time.
	 * @return bool
	 */
	public static function is_snoozed( int $dismissed_at, int $now ): bool {
		return $dismissed_at > 0 && ( $now - $dismissed_at ) < self::SNOOZE_SECONDS;
	}

	/**
	 * Warning text for the given problems and date (Y-m-d, site time).
	 *
	 * @param array<int, string> $problems Problems from problems().
	 * @param string             $today    Current date, Y-m-d.
	 * @return string
	 */
	public static function message( array $problems, string $today ): string {
		$lead = $today < self::DEADLINE
			? __( '2026. szeptember 27-től kötelező a harmonizált szavatossági tájékoztató és (érintett termékeknél) a GARAN címke. Ellenőrizd a beállításokat.', 'elallas-for-woo' )
			: __( 'A harmonizált szavatossági tájékoztató 2026. szeptember 27. óta kötelező.', 'elallas-for-woo' );

		$reasons = array_map( [ self::class, 'reason' ], $problems );

		return trim( $lead . ' ' . implode( ' ', array_filter( $reasons ) ) );
	}

	/**
	 * One sentence per problem.
	 *
	 * @param string $problem Problem key.
	 * @return string
	 */
	public static function reason( string $problem ): string {
		switch ( $problem ) {
			case self::NOTICE_OFF:
			case self::NOTICE_HIDDEN:
				return __( 'Jelenleg nem jelenik meg.', 'elallas-for-woo' );
			case self::CHECKOUT_OFF:
				return __( 'A pénztári megjelenítés ki van kapcsolva.', 'elallas-for-woo' );
			case self::GARAN_FILTERED:
				return __( 'Egy bővítmény vagy a téma szűrővel (elallas_garan_checkout_visible) elrejtheti a rendelés gomb előtti GARAN címkét.', 'elallas-for-woo' );
			case self::NOT_REVIEWED:
				return __( 'A beállítások még nincsenek átnézve.', 'elallas-for-woo' );
		}

		return '';
	}
}
