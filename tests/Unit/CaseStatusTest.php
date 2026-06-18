<?php
/**
 * CaseStatus tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Models\CaseStatus;

/**
 * @covers \LightweightPlugins\Elallas\Models\CaseStatus
 */
final class CaseStatusTest extends TestCase {

	public function test_known_statuses_are_valid(): void {
		$this->assertTrue( CaseStatus::is_valid( CaseStatus::RECEIVED ) );
		$this->assertTrue( CaseStatus::is_valid( CaseStatus::CLOSED ) );
	}

	public function test_unknown_status_is_invalid(): void {
		$this->assertFalse( CaseStatus::is_valid( 'nonsense' ) );
	}

	public function test_terminal_statuses(): void {
		$this->assertTrue( CaseStatus::is_terminal( CaseStatus::CLOSED ) );
		$this->assertTrue( CaseStatus::is_terminal( CaseStatus::REJECTED ) );
		$this->assertTrue( CaseStatus::is_terminal( CaseStatus::CANCELLED ) );
		$this->assertFalse( CaseStatus::is_terminal( CaseStatus::RECEIVED ) );
		$this->assertFalse( CaseStatus::is_terminal( CaseStatus::MANUAL_REVIEW ) );
	}

	public function test_label_falls_back_to_key(): void {
		$this->assertSame( 'unknown_key', CaseStatus::label( 'unknown_key' ) );
		$this->assertNotEmpty( CaseStatus::label( CaseStatus::RECEIVED ) );
	}

	public function test_all_statuses_have_labels(): void {
		$labels = CaseStatus::labels();
		foreach ( CaseStatus::all() as $status ) {
			$this->assertArrayHasKey( $status, $labels );
		}
	}
}
