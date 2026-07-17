<?php
/**
 * Deadline calculator.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

use LightweightPlugins\Elallas\Models\DeadlineStatus;

/**
 * Classifies whether a withdrawal is within the statutory deadline.
 *
 * Pure logic — never auto-blocks; the result is a flag for manual review.
 */
final class DeadlineCalculator {

	/**
	 * Seconds in a day (kept local for pure unit testing).
	 */
	private const DAY = 86400;

	/**
	 * Resolve the deadline start date string from available order dates.
	 *
	 * @param array{created: ?string, completed: ?string, delivery: ?string} $dates  Order dates.
	 * @param string                                                         $source order_created|order_completed|delivery|manual.
	 * @return string|null
	 */
	public static function resolve_start( array $dates, string $source ): ?string {
		return match ( $source ) {
			'order_created'   => $dates['created'] ?? null,
			'delivery'        => $dates['delivery'] ?? null,
			'order_completed' => $dates['completed'] ?? ( $dates['created'] ?? null ),
			default           => null,
		};
	}

	/**
	 * Classify the deadline status (pure).
	 *
	 * @param string|null $start_date Start date as a parseable string (or null).
	 * @param int         $days       Number of withdrawal days (e.g. 14).
	 * @param int|null    $now_ts     Reference "now" timestamp (defaults to time()).
	 * @return string DeadlineStatus constant.
	 */
	public static function status_for( ?string $start_date, int $days, ?int $now_ts = null ): string {
		if ( empty( $start_date ) ) {
			return DeadlineStatus::UNKNOWN;
		}

		$start_ts = strtotime( $start_date );

		if ( false === $start_ts ) {
			return DeadlineStatus::UNKNOWN;
		}

		$now_ts      = $now_ts ?? time();
		$deadline_ts = $start_ts + ( $days * self::DAY );

		return $now_ts <= $deadline_ts ? DeadlineStatus::WITHIN : DeadlineStatus::EXPIRED;
	}
}
