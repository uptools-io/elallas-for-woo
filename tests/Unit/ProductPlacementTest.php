<?php
/**
 * ProductPlacement tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\ProductPlacement;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\ProductPlacement
 */
final class ProductPlacementTest extends TestCase {

	protected function tearDown(): void {
		ProductPlacement::reset();
	}

	public function test_nothing_rendered_by_default(): void {
		$this->assertFalse( ProductPlacement::rendered( 'garan', 12 ) );
	}

	public function test_claim_is_per_product(): void {
		ProductPlacement::mark( 'garan', 12 );

		$this->assertTrue( ProductPlacement::rendered( 'garan', 12 ) );
		$this->assertFalse( ProductPlacement::rendered( 'garan', 13 ) );
	}

	public function test_claim_is_per_key(): void {
		ProductPlacement::mark( 'notice', 12 );

		$this->assertTrue( ProductPlacement::rendered( 'notice', 12 ) );
		$this->assertFalse( ProductPlacement::rendered( 'garan', 12 ) );
	}

	public function test_reset_forgets_claims(): void {
		ProductPlacement::mark( 'garan', 12 );
		ProductPlacement::reset();

		$this->assertFalse( ProductPlacement::rendered( 'garan', 12 ) );
	}
}
