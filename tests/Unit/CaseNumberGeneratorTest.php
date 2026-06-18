<?php
/**
 * CaseNumberGenerator tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Domain\CaseNumberGenerator;

/**
 * @covers \LightweightPlugins\Elallas\Domain\CaseNumberGenerator
 */
final class CaseNumberGeneratorTest extends TestCase {

	public function test_format_pads_year_and_sequence(): void {
		$this->assertSame( 'EL-2026-000001', CaseNumberGenerator::format( 2026, 1 ) );
		$this->assertSame( 'EL-2026-000042', CaseNumberGenerator::format( 2026, 42 ) );
		$this->assertSame( 'EL-2026-123456', CaseNumberGenerator::format( 2026, 123456 ) );
	}

	public function test_format_handles_large_sequence(): void {
		$this->assertSame( 'EL-2027-1000000', CaseNumberGenerator::format( 2027, 1000000 ) );
	}
}
