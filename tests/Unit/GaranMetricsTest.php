<?php
/**
 * GaranMetrics tests.
 *
 * Fixtures are the kerning-free advance sums of the bundled Inter 3.19
 * (GaranMetricsTable). They sit within 0.5 unit of the M0 browser measurement
 * (bin/measure-garan-fit.html) and are never narrower than the kerned text.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\GaranMetrics;
use LightweightPlugins\Elallas\Compliance\GaranMetricsTable;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\GaranMetrics
 */
final class GaranMetricsTest extends TestCase {

	public function test_model_placeholder_width(): void {
		$this->assertEqualsWithDelta( 66.5, GaranMetrics::width( 'Model identifier', GaranMetrics::WEIGHT_REGULAR, 9.0 ), 0.5 );
	}

	public function test_years_placeholder_width_full_label(): void {
		$width = GaranMetrics::width( 'XX', GaranMetrics::WEIGHT_EXTRABOLD, 80.0, -0.03 );
		$this->assertEqualsWithDelta( 113.4, $width, 0.5 );
		// Ends at 118.5 from the text origin x = 5.07.
		$this->assertEqualsWithDelta( 118.5, 5.07 + $width, 0.5 );
	}

	/**
	 * @return array<string, array{0: string, 1: float}>
	 */
	public static function model_widths(): array {
		return [
			'samsung 14 chars' => [ 'SM-S921BZKDEUE', 80.1 ],
			'apple'            => [ 'A2848', 28.4 ],
		];
	}

	/**
	 * @dataProvider model_widths
	 */
	public function test_real_model_widths( string $model, float $expected ): void {
		$this->assertEqualsWithDelta( $expected, GaranMetrics::width( $model, GaranMetrics::WEIGHT_REGULAR, 9.0 ), 0.5 );
	}

	public function test_fits_model(): void {
		$this->assertFalse( GaranMetrics::fits_model( 'Model identifier' ) ); // 66.5 > 66.
		$this->assertFalse( GaranMetrics::fits_model( 'SM-S921BZKDEUE' ) );  // 80.1 > 66.
		$this->assertFalse( GaranMetrics::fits_model( 'MQ-2024-PRO-MAX1' ) );
		$this->assertTrue( GaranMetrics::fits_model( 'A2848' ) );
		$this->assertTrue( GaranMetrics::fits_model( 'WAN28281BY' ) );
	}

	public function test_fits_brand(): void {
		$this->assertTrue( GaranMetrics::fits_brand( 'Robert Bosch Hausgeräte GmbH' ) );
		$this->assertTrue( GaranMetrics::fits_brand( 'Samsung' ) );
		$this->assertFalse( GaranMetrics::fits_brand( 'ELECTROLUX HOME PRODUCTS EUROPE BVBA 2026' ) ); // 229 > 185.
	}

	public function test_every_whole_year_fits_both_labels(): void {
		for ( $i = 3; $i <= 99; $i++ ) {
			$this->assertTrue( GaranMetrics::fits_years( (string) $i ), "full $i" );
			$this->assertTrue( GaranMetrics::fits_years( (string) $i, true ), "nested $i" );
		}
	}

	public function test_half_years_do_not_fit_without_kerning(): void {
		foreach ( [ '2,5', '4,5', '7,5', '9,5', '12,5' ] as $label ) {
			$this->assertFalse( GaranMetrics::fits_years( $label ), $label );
		}
		$this->assertFalse( GaranMetrics::fits_years( '99,5', true ) );
	}

	public function test_placeholder_does_not_fit_nested_with_margin(): void {
		// XX (placeholder) nearly fills the nested slot; no safety margin left.
		$this->assertFalse( GaranMetrics::fits_years( 'XX', true ) );
	}

	public function test_unknown_character_counts_as_widest_glyph(): void {
		$expected = GaranMetricsTable::MAX_REGULAR / GaranMetricsTable::UNITS_PER_EM * 9.0;
		$this->assertEqualsWithDelta( $expected, GaranMetrics::width( "\u{4E2D}", GaranMetrics::WEIGHT_REGULAR, 9.0 ), 0.0001 );
	}

	public function test_invalid_utf8_never_fits(): void {
		$this->assertFalse( GaranMetrics::fits_brand( "\xC3\x28" ) );
		$this->assertSame( 0.0, GaranMetrics::width( '', GaranMetrics::WEIGHT_REGULAR, 9.0 ) );
	}
}
