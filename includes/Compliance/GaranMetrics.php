<?php
/**
 * GARAN field-width checks without GD (pure).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * Measures text with the bundled Inter 3.19 advance widths (GaranMetricsTable)
 * and decides whether a value fits its fixed slot on the official label.
 *
 * Widths are in SVG user units (1 unit = 1 CSS px at scale 1). Kerning is
 * ignored on purpose: the plain advance sum is never narrower than the kerned
 * rendering, so a value accepted here does not overlap the fixed elements.
 * The font size and letter spacing are never reduced to make a value fit.
 */
final class GaranMetrics {

	public const WEIGHT_REGULAR   = 'regular';
	public const WEIGHT_EXTRABOLD = 'extrabold';

	/**
	 * Brand / model: `.cls-5` (Inter-Regular 9px, no letter spacing).
	 */
	public const FIELD_PX = 9.0;

	/**
	 * Brand slot: from translate(6.32 …) to the QR block (~185 units).
	 */
	public const BRAND_MAX = 185.0;

	/**
	 * Model slot: translate(196.75 …) to the separator end x2=262.97 (~66 units).
	 */
	public const MODEL_MAX = 66.0;

	/**
	 * Years, full label: `.cls-3` (ExtraBold 80px, -0.03em); free space
	 * 121.47 (calendar icon) − 5.07 (text origin).
	 */
	public const YEARS_FULL_PX  = 80.0;
	public const YEARS_FULL_LS  = -0.03;
	public const YEARS_FULL_MAX = 116.4;

	/**
	 * Years, nested label: `.cls-1` (ExtraBold 41.56px, -0.02em); free space
	 * 70.87 − 10.39.
	 */
	public const YEARS_NESTED_PX  = 41.56;
	public const YEARS_NESTED_LS  = -0.02;
	public const YEARS_NESTED_MAX = 60.5;

	/**
	 * Safety margin (units) kept free in every slot.
	 */
	public const SAFETY_MARGIN = 0.5;

	/**
	 * Text width in user units, CSS semantics (letter spacing after every glyph).
	 *
	 * Unknown characters count as the widest glyph of the table (safe side).
	 *
	 * @param string $text           UTF-8 text.
	 * @param string $weight         self::WEIGHT_REGULAR|self::WEIGHT_EXTRABOLD.
	 * @param float  $px             Font size.
	 * @param float  $letter_spacing Letter spacing in em.
	 * @return float
	 */
	public static function width( string $text, string $weight, float $px, float $letter_spacing = 0.0 ): float {
		$chars = self::chars( $text );
		if ( null === $chars ) {
			return INF; // Invalid UTF-8 never fits.
		}
		if ( [] === $chars ) {
			return 0.0;
		}

		$bold  = self::WEIGHT_EXTRABOLD === $weight;
		$table = $bold ? GaranMetricsTable::EXTRABOLD : GaranMetricsTable::REGULAR;
		$max   = $bold ? GaranMetricsTable::MAX_EXTRABOLD : GaranMetricsTable::MAX_REGULAR;
		$units = 0;

		foreach ( $chars as $char ) {
			$units += $table[ self::code_point( $char ) ] ?? $max;
		}

		return $units / GaranMetricsTable::UNITS_PER_EM * $px + count( $chars ) * $letter_spacing * $px;
	}

	/**
	 * Whether a brand fits its slot (9px Inter Regular ≤ 185 units).
	 *
	 * @param string $brand Brand / trademark.
	 * @return bool
	 */
	public static function fits_brand( string $brand ): bool {
		return self::width( $brand, self::WEIGHT_REGULAR, self::FIELD_PX ) + self::SAFETY_MARGIN <= self::BRAND_MAX;
	}

	/**
	 * Whether a model identifier fits its slot (9px Inter Regular ≤ 66 units).
	 *
	 * @param string $model Model identifier.
	 * @return bool
	 */
	public static function fits_model( string $model ): bool {
		return self::width( $model, self::WEIGHT_REGULAR, self::FIELD_PX ) + self::SAFETY_MARGIN <= self::MODEL_MAX;
	}

	/**
	 * Whether a years label (e.g. `3`, `2,5`) fits the full or the nested label.
	 *
	 * The trailing (negative) letter spacing is not credited: the last glyph's
	 * full advance counts.
	 *
	 * @param string $label  Years as printed (decimal comma).
	 * @param bool   $nested Nested variant.
	 * @return bool
	 */
	public static function fits_years( string $label, bool $nested = false ): bool {
		$px    = $nested ? self::YEARS_NESTED_PX : self::YEARS_FULL_PX;
		$ls    = $nested ? self::YEARS_NESTED_LS : self::YEARS_FULL_LS;
		$limit = $nested ? self::YEARS_NESTED_MAX : self::YEARS_FULL_MAX;
		$width = self::width( $label, self::WEIGHT_EXTRABOLD, $px, $ls ) - $ls * $px;

		return $width + self::SAFETY_MARGIN <= $limit;
	}

	/**
	 * Split into UTF-8 characters.
	 *
	 * @param string $text Text.
	 * @return array<int, string>|null Null for invalid UTF-8.
	 */
	private static function chars( string $text ): ?array {
		$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );

		return is_array( $chars ) ? $chars : null;
	}

	/**
	 * Code point of one UTF-8 character.
	 *
	 * @param string $char Character.
	 * @return int
	 */
	private static function code_point( string $char ): int {
		// mb_ord() returns false only for invalid input, already rejected by chars(); 0 is not in the table.
		return (int) mb_ord( $char, 'UTF-8' );
	}
}
