<?php
/**
 * Official asset integrity tests (CI gate against modified official files).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\OfficialAssets;

/**
 * @coversNothing
 */
final class ComplianceChecksumTest extends TestCase {

	public function test_garan_svgs_match_expected_sha256(): void {
		$root = dirname( __DIR__, 2 ) . '/';
		foreach ( OfficialAssets::GARAN_SVG as $relpath ) {
			$this->assertSame(
				OfficialAssets::SVG_SHA256[ basename( $relpath ) ],
				hash_file( 'sha256', $root . $relpath ),
				$relpath . ' differs from the official file'
			);
		}
	}

	public function test_checksum_manifest_matches_files(): void {
		$root  = dirname( __DIR__, 2 ) . '/';
		$lines = file( $root . 'assets/CHECKSUMS.sha256', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$this->assertIsArray( $lines );
		$this->assertGreaterThanOrEqual( 24 + 23 + 3 + 5, count( $lines ) );

		foreach ( $lines as $line ) {
			$this->assertSame( 1, preg_match( '/^([0-9a-f]{64}) [ *](assets\/\S.*)$/', $line, $m ), 'Malformed line: ' . $line );
			$this->assertFileExists( $root . $m[2] );
			$this->assertSame( $m[1], hash_file( 'sha256', $root . $m[2] ), $m[2] . ' was modified' );
		}
	}

	public function test_manifest_covers_every_official_file(): void {
		$root     = dirname( __DIR__, 2 ) . '/';
		$manifest = (string) file_get_contents( $root . 'assets/CHECKSUMS.sha256' );
		$files    = array_merge(
			(array) glob( $root . 'assets/notice/*' ),
			(array) glob( $root . 'assets/garan/*' ),
			(array) glob( $root . 'assets/fonts/inter/*' )
		);
		foreach ( $files as $file ) {
			$rel = substr( (string) $file, strlen( $root ) );
			$this->assertStringContainsString( '  ' . $rel . "\n", $manifest, $rel . ' is missing from CHECKSUMS.sha256' );
		}
	}
}
