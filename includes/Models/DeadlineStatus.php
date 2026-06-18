<?php
/**
 * Deadline status value object.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Models;

/**
 * Deadline classification — flag, never an automatic block.
 */
final class DeadlineStatus {

	public const WITHIN  = 'within';
	public const EXPIRED = 'expired';
	public const UNKNOWN = 'unknown';

	/**
	 * Labels keyed by status.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return [
			self::WITHIN  => __( 'Határidőn belül', 'elallas-for-woo' ),
			self::EXPIRED => __( 'Határidőn túl – manuális ellenőrzést igényel', 'elallas-for-woo' ),
			self::UNKNOWN => __( 'Nem megállapítható – manuális ellenőrzést igényel', 'elallas-for-woo' ),
		];
	}

	/**
	 * Human label for a deadline status.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	public static function label( string $status ): string {
		$labels = self::labels();
		return $labels[ $status ] ?? $status;
	}
}
