<?php
/**
 * OfficialAssets tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\OfficialAssets;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\OfficialAssets
 */
final class OfficialAssetsTest extends TestCase {

	/**
	 * QR targets decoded from the official files (bin/decode-notice-qr.py, M0).
	 * EN has no official PNG; its QR was decoded from the official EN SVG.
	 */
	private const QR_FIXTURE = [
		'bg' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_bg.htm',
		'cs' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_cs.htm',
		'da' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_da.htm',
		'de' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_de.htm',
		'el' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_el.htm',
		'en' => 'https://europa.eu/youreurope/guarantees',
		'es' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_es.htm',
		'et' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_et.htm',
		'fi' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_fi.htm',
		'fr' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_fr.htm',
		'ga' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_ga.htm',
		'hr' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_hr.htm',
		'hu' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_hu.htm',
		'it' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_it.htm',
		'lt' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_lt.htm',
		'lv' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_lv.htm',
		'mt' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_mt.htm',
		'nl' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_nl.htm',
		'pl' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_pl.htm',
		'pt' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_pt.htm',
		'ro' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_ro.htm',
		'sk' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_sk.htm',
		'sl' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_sl.htm',
		'sv' => 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_sv.htm',
	];

	/**
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
	 */
	public static function resolve_provider(): array {
		return [
			'plain'           => [ 'hu', '', '', 'hu' ],
			'locale'          => [ 'hu_HU', '', '', 'hu' ],
			'pt-pt'           => [ 'pt-pt', '', '', 'pt' ],
			'en-gb'           => [ 'en-gb', '', '', 'en' ],
			'default wins'    => [ 'xx', 'de', 'hu_HU', 'de' ],
			'locale wins'     => [ 'xx', '', 'sv_SE', 'sv' ],
			'final fallback'  => [ 'xx', '', 'xx_XX', 'hu' ],
			'upper and space' => [ ' DE ', '', '', 'de' ],
		];
	}

	/**
	 * @dataProvider resolve_provider
	 */
	public function test_resolve_code( string $current, string $fallback, string $locale, string $expected ): void {
		$this->assertSame( $expected, OfficialAssets::resolve_code( $current, $fallback, $locale ) );
	}

	public function test_there_are_24_languages(): void {
		$this->assertCount( 24, OfficialAssets::codes() );
		$this->assertSame( OfficialAssets::codes(), array_keys( OfficialAssets::LINKS ) );
		$this->assertSame( OfficialAssets::codes(), array_keys( OfficialAssets::LINK_LABELS ) );
	}

	public function test_links_match_decoded_qr_targets(): void {
		$this->assertSame( self::QR_FIXTURE, OfficialAssets::LINKS );
		$this->assertSame( 'https://europa.eu/youreurope/guarantees', OfficialAssets::link_url( 'en' ) );
		$this->assertStringEndsWith( '/guarantees-returns/index_hu.htm', OfficialAssets::link_url( 'hu' ) );
		$this->assertStringEndsWith( '/guarantees-returns/index_de.htm', OfficialAssets::link_url( 'de' ) );
		$this->assertSame( OfficialAssets::link_url( 'hu' ), OfficialAssets::link_url( 'xx' ) );
	}

	public function test_links_are_https_europa(): void {
		foreach ( OfficialAssets::LINKS as $url ) {
			$this->assertSame( 'https', parse_url( $url, PHP_URL_SCHEME ) );
			$this->assertSame( 'europa.eu', parse_url( $url, PHP_URL_HOST ) );
		}
	}

	public function test_link_labels(): void {
		$this->assertSame( 'europa.eu/youreurope/jótállás', OfficialAssets::link_label( 'hu' ) );
		$this->assertSame( 'europa.eu/youreurope/guarantees', OfficialAssets::link_label( 'en' ) );
		$this->assertSame( 'europa.eu/youreurope/jótállás', OfficialAssets::link_label( 'xx' ) );
	}

	public function test_every_language_has_existing_files(): void {
		$root = dirname( __DIR__, 2 ) . '/';
		foreach ( OfficialAssets::codes() as $code ) {
			$svg = OfficialAssets::svg_relpath( $code );
			$this->assertSame( 'assets/notice/notice-' . $code . '.svg', $svg );
			$this->assertFileExists( $root . $svg );

			$png = OfficialAssets::png_relpath( $code );
			if ( 'en' === $code ) {
				$this->assertFalse( OfficialAssets::has_png( $code ) );
				$this->assertSame( '', $png );
				$this->assertFileDoesNotExist( $root . 'assets/notice/notice-en.png' );
				continue;
			}
			$this->assertSame( 'assets/notice/notice-' . $code . '.png', $png );
			$this->assertFileExists( $root . $png );
			$size = getimagesize( $root . $png );
			$this->assertIsArray( $size );
			$this->assertSame( [ OfficialAssets::PNG_SIZE['width'], OfficialAssets::PNG_SIZE['height'] ], [ $size[0], $size[1] ] );
		}
	}

	public function test_unsupported_code_has_no_paths(): void {
		$this->assertSame( '', OfficialAssets::png_relpath( '../x' ) );
		$this->assertSame( '', OfficialAssets::svg_relpath( 'xx' ) );
		$this->assertSame( '', OfficialAssets::garan_svg_relpath( 'bw' ) );
	}

	public function test_garan_files_exist(): void {
		$root = dirname( __DIR__, 2 ) . '/';
		foreach ( [ 'colour', 'nested' ] as $variant ) {
			$this->assertFileExists( $root . OfficialAssets::garan_svg_relpath( $variant ) );
		}
	}
}
