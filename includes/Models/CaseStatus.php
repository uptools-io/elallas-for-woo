<?php
/**
 * Case status value object.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Models;

/**
 * Withdrawal case statuses and their labels.
 */
final class CaseStatus {

	public const RECEIVED        = 'received';
	public const AUTO_CONFIRMED  = 'auto_confirmed';
	public const MANUAL_REVIEW   = 'manual_review';
	public const ACCEPTED        = 'accepted';
	public const REJECTED        = 'rejected';
	public const AWAITING_RETURN = 'awaiting_return';
	public const GOODS_RECEIVED  = 'goods_received';
	public const REFUND_PENDING  = 'refund_pending';
	public const CLOSED          = 'closed';
	public const CANCELLED       = 'cancelled';

	/**
	 * All status keys in workflow order.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return [
			self::RECEIVED,
			self::AUTO_CONFIRMED,
			self::MANUAL_REVIEW,
			self::ACCEPTED,
			self::REJECTED,
			self::AWAITING_RETURN,
			self::GOODS_RECEIVED,
			self::REFUND_PENDING,
			self::CLOSED,
			self::CANCELLED,
		];
	}

	/**
	 * Status labels keyed by status.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return [
			self::RECEIVED        => __( 'Beérkezett', 'elallas-for-woo' ),
			self::AUTO_CONFIRMED  => __( 'Automatikusan visszaigazolva', 'elallas-for-woo' ),
			self::MANUAL_REVIEW   => __( 'Manuális ellenőrzés alatt', 'elallas-for-woo' ),
			self::ACCEPTED        => __( 'Elfogadva', 'elallas-for-woo' ),
			self::REJECTED        => __( 'Elutasítva', 'elallas-for-woo' ),
			self::AWAITING_RETURN => __( 'Visszaküldésre vár', 'elallas-for-woo' ),
			self::GOODS_RECEIVED  => __( 'Áru beérkezett', 'elallas-for-woo' ),
			self::REFUND_PENDING  => __( 'Visszatérítés folyamatban', 'elallas-for-woo' ),
			self::CLOSED          => __( 'Lezárva', 'elallas-for-woo' ),
			self::CANCELLED       => __( 'Törölve / hibás beküldés', 'elallas-for-woo' ),
		];
	}

	/**
	 * Human label for a status.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	public static function label( string $status ): string {
		$labels = self::labels();
		return $labels[ $status ] ?? $status;
	}

	/**
	 * Whether a status is valid.
	 *
	 * @param string $status Status key.
	 * @return bool
	 */
	public static function is_valid( string $status ): bool {
		return in_array( $status, self::all(), true );
	}

	/**
	 * Whether a status is terminal (no further workflow).
	 *
	 * @param string $status Status key.
	 * @return bool
	 */
	public static function is_terminal( string $status ): bool {
		return in_array( $status, [ self::CLOSED, self::REJECTED, self::CANCELLED ], true );
	}
}
