<?php
/**
 * CaseService::confirm() tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

require_once dirname( __DIR__ ) . '/Support/FlowStubs.php';

use Elallas_Flow_Stub;
use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Domain\CaseService;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Models\DeadlineStatus;

/**
 * @covers \LightweightPlugins\Elallas\Domain\CaseService
 */
final class CaseServiceConfirmTest extends TestCase {

	protected function setUp(): void {
		Elallas_Flow_Stub::reset();
	}

	private function seed_case( string $status, string $deadline = DeadlineStatus::WITHIN ): void {
		Elallas_Flow_Stub::$cases[7] = [
			'id'              => 7,
			'case_number'     => 'EL-7',
			'order_id'        => 5,
			'status'          => $status,
			'deadline_status' => $deadline,
		];
	}

	public function test_confirm_within_deadline(): void {
		$this->seed_case( CaseStatus::RECEIVED );

		$this->assertTrue( ( new CaseService() )->confirm( 7 ) );
		$this->assertSame( CaseStatus::AUTO_CONFIRMED, Elallas_Flow_Stub::$updates[0][1]['status'] );
		$this->assertContains( 'elallas_case_confirmed', Elallas_Flow_Stub::$actions );
	}

	public function test_confirm_outside_deadline(): void {
		$this->seed_case( CaseStatus::RECEIVED, DeadlineStatus::EXPIRED );

		$this->assertTrue( ( new CaseService() )->confirm( 7 ) );
		$this->assertSame( CaseStatus::MANUAL_REVIEW, Elallas_Flow_Stub::$updates[0][1]['status'] );
	}

	public function test_confirm_unknown_case(): void {
		$this->assertFalse( ( new CaseService() )->confirm( 99 ) );
		$this->assertSame( [], Elallas_Flow_Stub::$updates );
	}

	/**
	 * @dataProvider statuses
	 */
	public function test_confirm_rules( string $status ): void {
		$this->seed_case( $status );

		$this->assertFalse( ( new CaseService() )->confirm( 7 ) );
		$this->assertSame( [], Elallas_Flow_Stub::$updates );
		$this->assertSame( [], Elallas_Flow_Stub::$inserts );
		$this->assertSame( [], Elallas_Flow_Stub::$actions );
	}

	/**
	 * @return array<int, array{0: string}>
	 */
	public static function statuses(): array {
		$rows = [];

		foreach ( CaseStatus::all() as $status ) {
			if ( CaseStatus::RECEIVED !== $status ) {
				$rows[] = [ $status ];
			}
		}

		return $rows;
	}
}
