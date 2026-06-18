<?php
/**
 * Case number generator.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

/**
 * Generates sequential, human-readable case numbers (EL-YYYY-NNNNNN).
 */
final class CaseNumberGenerator {

	/**
	 * Counter option key.
	 */
	public const OPTION = 'lw_elallas_case_counter';

	/**
	 * Generate the next case number.
	 *
	 * @return string
	 */
	public static function generate(): string {
		$year    = (int) current_time( 'Y' );
		$counter = get_option( self::OPTION, [] );
		$counter = is_array( $counter ) ? $counter : [];

		$seq                 = (int) ( $counter[ $year ] ?? 0 ) + 1;
		$counter[ (string) $year ] = $seq;

		update_option( self::OPTION, $counter );

		return self::format( $year, $seq );
	}

	/**
	 * Format a case number (pure).
	 *
	 * @param int $year Year.
	 * @param int $seq  Sequence number within the year.
	 * @return string
	 */
	public static function format( int $year, int $seq ): string {
		return sprintf( 'EL-%04d-%06d', $year, $seq );
	}
}
