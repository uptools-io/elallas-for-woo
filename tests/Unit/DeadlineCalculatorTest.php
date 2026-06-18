<?php
/**
 * DeadlineCalculator tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Domain\DeadlineCalculator;
use LightweightPlugins\Elallas\Models\DeadlineStatus;

/**
 * @covers \LightweightPlugins\Elallas\Domain\DeadlineCalculator
 */
final class DeadlineCalculatorTest extends TestCase {

	public function test_unknown_when_no_start_date(): void {
		$this->assertSame( DeadlineStatus::UNKNOWN, DeadlineCalculator::status_for( null, 14 ) );
		$this->assertSame( DeadlineStatus::UNKNOWN, DeadlineCalculator::status_for( '', 14 ) );
	}

	public function test_unknown_when_unparseable(): void {
		$this->assertSame( DeadlineStatus::UNKNOWN, DeadlineCalculator::status_for( 'not-a-date', 14 ) );
	}

	public function test_within_when_inside_window(): void {
		$start = '2026-06-10 10:00:00';
		$now   = strtotime( '2026-06-18 10:00:00' );
		$this->assertSame( DeadlineStatus::WITHIN, DeadlineCalculator::status_for( $start, 14, $now ) );
	}

	public function test_expired_when_past_window(): void {
		$start = '2026-06-01 10:00:00';
		$now   = strtotime( '2026-06-18 10:00:00' );
		$this->assertSame( DeadlineStatus::EXPIRED, DeadlineCalculator::status_for( $start, 14, $now ) );
	}

	public function test_boundary_is_inclusive(): void {
		$start = '2026-06-01 00:00:00';
		$now   = strtotime( '2026-06-15 00:00:00' ); // Exactly 14 days later.
		$this->assertSame( DeadlineStatus::WITHIN, DeadlineCalculator::status_for( $start, 14, $now ) );
	}

	public function test_resolve_start_by_source(): void {
		$dates = [ 'created' => '2026-01-01 00:00:00', 'completed' => '2026-01-05 00:00:00', 'delivery' => '2026-01-08 00:00:00' ];

		$this->assertSame( '2026-01-01 00:00:00', DeadlineCalculator::resolve_start( $dates, 'order_created' ) );
		$this->assertSame( '2026-01-05 00:00:00', DeadlineCalculator::resolve_start( $dates, 'order_completed' ) );
		$this->assertSame( '2026-01-08 00:00:00', DeadlineCalculator::resolve_start( $dates, 'delivery' ) );
		$this->assertNull( DeadlineCalculator::resolve_start( $dates, 'manual' ) );
	}

	public function test_resolve_completed_falls_back_to_created(): void {
		$dates = [ 'created' => '2026-01-01 00:00:00', 'completed' => null, 'delivery' => null ];
		$this->assertSame( '2026-01-01 00:00:00', DeadlineCalculator::resolve_start( $dates, 'order_completed' ) );
	}
}
