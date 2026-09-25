<?php
/**
 * GaranRaster drawing tests (need GD with FreeType).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\GaranData;
use LightweightPlugins\Elallas\Compliance\GaranRaster;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\GaranRaster
 */
final class GaranRasterTest extends TestCase {

	private static function root(): string {
		return dirname( __DIR__, 2 ) . '/';
	}

	protected function setUp(): void {
		if ( ! GaranRaster::can_render( self::root() ) ) {
			$this->markTestSkipped( 'GD with FreeType is not available.' );
		}
	}

	/**
	 * Count dark pixels in a rectangle (4x coordinates).
	 */
	private static function dark( \GdImage $im, int $x0, int $y0, int $x1, int $y1 ): int {
		$n = 0;
		for ( $x = $x0; $x < $x1; $x += 2 ) {
			for ( $y = $y0; $y < $y1; $y += 2 ) {
				$c = imagecolorsforindex( $im, imagecolorat( $im, $x, $y ) );
				if ( $c['red'] < 100 && $c['green'] < 100 && $c['blue'] < 100 ) {
					++$n;
				}
			}
		}
		return $n;
	}

	public function test_base_has_empty_fields_and_draw_fills_them(): void {
		$base = imagecreatefrompng( self::root() . GaranRaster::BASE );
		$this->assertSame( 1077, imagesx( $base ) );
		$this->assertSame( 1134, imagesy( $base ) );

		// Brand slot (x 25–700, above baseline 298), model slot (x 787–1050), years (x 20–480, y 330–605).
		$this->assertSame( 0, self::dark( $base, 25, 262, 700, 298 ) );
		$this->assertSame( 0, self::dark( $base, 787, 262, 1050, 298 ) );
		$this->assertSame( 0, self::dark( $base, 20, 380, 470, 600 ) );

		$data = GaranData::from_input( '40', 'Miele', 'WM 7000', false );
		$this->assertNotNull( $data );
		$im = GaranRaster::draw( $data, self::root() );
		$this->assertInstanceOf( \GdImage::class, $im );
		$this->assertGreaterThan( 20, self::dark( $im, 25, 262, 700, 298 ) );
		$this->assertGreaterThan( 20, self::dark( $im, 787, 262, 1050, 298 ) );
		$this->assertGreaterThan( 500, self::dark( $im, 20, 380, 470, 600 ) );
		// The calendar icon area right of the years stays unchanged.
		$this->assertSame( self::dark( $base, 486, 500, 600, 610 ), self::dark( $im, 486, 500, 600, 610 ) );
	}

	public function test_downscale_keeps_the_aspect_ratio(): void {
		$data = GaranData::from_input( '3', 'Apple', 'A2848', false );
		$this->assertNotNull( $data );
		$im    = GaranRaster::draw( $data, self::root() );
		$small = GaranRaster::downscale( $im );
		$this->assertSame( GaranRaster::OUT_WIDTH, imagesx( $small ) );
		$this->assertSame( (int) round( 1134 * GaranRaster::OUT_WIDTH / 1077 ), imagesy( $small ) );
	}

	public function test_store_png_writes_atomically_without_leftovers(): void {
		$dir = sys_get_temp_dir() . '/elallas-raster-' . bin2hex( random_bytes( 4 ) );
		mkdir( $dir );
		$path = $dir . '/garan.png';
		file_put_contents( $path, 'old' );

		$im = imagecreatetruecolor( 4, 4 );
		$this->assertTrue( GaranRaster::store_png( $im, $path ) );

		$this->assertSame( "\x89PNG", substr( (string) file_get_contents( $path ), 0, 4 ) );
		$this->assertSame( [ 'garan.png' ], array_values( array_diff( scandir( $dir ), [ '.', '..' ] ) ) );

		unlink( $path );
		rmdir( $dir );
	}

	public function test_store_png_fails_cleanly_without_directory(): void {
		$path = sys_get_temp_dir() . '/elallas-missing-' . bin2hex( random_bytes( 4 ) ) . '/garan.png';

		$this->assertFalse( GaranRaster::store_png( imagecreatetruecolor( 4, 4 ), $path ) );
		$this->assertFileDoesNotExist( $path );
	}
}
