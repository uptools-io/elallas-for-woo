<?php
/**
 * GaranData tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\GaranData;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\GaranData
 */
final class GaranDataTest extends TestCase {

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function valid_whole_years(): array {
		return [
			'3'        => [ '3', '3' ],
			'padded'   => [ ' 5 ', '5' ],
			'10'       => [ '10', '10' ],
			'12'       => [ '12', '12' ],
			'widest'   => [ '40', '40' ],
			'max'      => [ '99', '99' ],
			'3.0 dot'  => [ '3.0', '3' ],
		];
	}

	/**
	 * @dataProvider valid_whole_years
	 */
	public function test_valid_whole_years( string $raw, string $expected ): void {
		$this->assertSame( $expected, GaranData::normalize_years( $raw, false ) );
		$this->assertSame( $expected, GaranData::normalize_years( $raw, true ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function invalid_years(): array {
		$cases = [];
		foreach ( [ '10,5', '12,5', '99,5', '2', '2.0', '2.4', '2,25', '4,1', '0', 'abc', '100', '', '-3', '3e0', '1/2' ] as $raw ) {
			$cases[ "'$raw'" ] = [ $raw ];
		}
		return $cases;
	}

	/**
	 * @dataProvider invalid_years
	 */
	public function test_invalid_years_in_both_modes( string $raw ): void {
		$this->assertNull( GaranData::normalize_years( $raw, false ) );
		$this->assertNull( GaranData::normalize_years( $raw, true ) );
	}

	public function test_half_years_rejected_when_not_allowed(): void {
		$this->assertNull( GaranData::normalize_years( '2,5', false ) );
		$this->assertNull( GaranData::normalize_years( '4,5', false ) );
	}

	public function test_half_years_rejected_even_when_allowed_because_they_overlap(): void {
		// Advance-sum widths (no kerning): every x,5 reaches the calendar icon (M0 decision 2).
		$this->assertNull( GaranData::normalize_years( '2,5', true ) );
		$this->assertNull( GaranData::normalize_years( '9,5', true ) );
	}

	public function test_years_label_and_half_year_flag(): void {
		$data = GaranData::from_input( '3', 'Apple', 'A2848', false );
		$this->assertNotNull( $data );
		$this->assertSame( '3', $data->years_label() );
		$this->assertFalse( $data->is_half_year() );
	}

	public function test_from_input_valid(): void {
		$data = GaranData::from_input( ' 5 ', ' Samsung ', 'A2848', false );
		$this->assertNotNull( $data );
		$this->assertSame( [ 'years' => '5', 'brand' => 'Samsung', 'model' => 'A2848' ], $data->to_array() );
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
	 */
	public static function invalid_inputs(): array {
		return [
			'empty brand'   => [ '3', '', 'A2848', 'brand', GaranData::ERR_EMPTY ],
			'empty model'   => [ '3', 'Apple', '  ', 'model', GaranData::ERR_EMPTY ],
			'wide model'    => [ '3', 'Samsung', 'SM-S921BZKDEUE', 'model', GaranData::ERR_TOO_WIDE ],
			'wide brand'    => [ '3', 'ELECTROLUX HOME PRODUCTS EUROPE BVBA 2026', 'A2848', 'brand', GaranData::ERR_TOO_WIDE ],
			'control char'  => [ '3', "Ap\x07ple", 'A2848', 'brand', GaranData::ERR_CONTROL ],
			'bad utf8'      => [ '3', 'Apple', "\xC3\x28", 'model', GaranData::ERR_CONTROL ],
			'two years'     => [ '2', 'Apple', 'A2848', 'years', GaranData::ERR_INVALID ],
			'empty years'   => [ '', 'Apple', 'A2848', 'years', GaranData::ERR_EMPTY ],
		];
	}

	/**
	 * @dataProvider invalid_inputs
	 */
	public function test_from_input_invalid( string $years, string $brand, string $model, string $field, string $code ): void {
		$this->assertNull( GaranData::from_input( $years, $brand, $model, false ) );
		$errors = GaranData::errors( $years, $brand, $model, false );
		$this->assertArrayHasKey( $field, $errors );
		$this->assertSame( $code, $errors[ $field ] );
	}

	public function test_round_trip(): void {
		$data = GaranData::from_input( '7', 'Miele & Cie. KG', 'WM 7000', false );
		$this->assertNotNull( $data );
		$copy = GaranData::from_array( $data->to_array() );
		$this->assertNotNull( $copy );
		$this->assertSame( $data->to_array(), $copy->to_array() );
	}

	public function test_from_array_rejects_broken_snapshots(): void {
		$this->assertNull( GaranData::from_array( [] ) );
		$this->assertNull( GaranData::from_array( [ 'years' => '3', 'brand' => 'Apple' ] ) );
		$this->assertNull( GaranData::from_array( [ 'years' => [ 3 ], 'brand' => 'Apple', 'model' => 'A1' ] ) );
		$this->assertNull( GaranData::from_array( [ 'years' => '3', 'brand' => 'Apple', 'model' => 'SM-S921BZKDEUE' ] ) );
	}

	public function test_hash_is_deterministic_and_depends_on_asset_version(): void {
		$a = GaranData::from_input( '3', 'Apple', 'A2848', false );
		$b = GaranData::from_input( '3', 'Apple', 'A2848', false );
		$this->assertNotNull( $a );
		$this->assertNotNull( $b );
		$this->assertSame( $a->hash( '2025-10' ), $b->hash( '2025-10' ) );
		$this->assertNotSame( $a->hash( '2025-10' ), $a->hash( '2026-01' ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{40}$/', $a->hash( '2025-10' ) );
	}
}
