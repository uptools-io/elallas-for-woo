<?php
/**
 * ComplianceCheck tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Admin\ComplianceCheck;

/**
 * @covers \LightweightPlugins\Elallas\Admin\ComplianceCheck
 */
final class ComplianceCheckTest extends TestCase {

	/**
	 * Defaults of a reviewed, correctly configured shop.
	 *
	 * @param array<string, mixed> $overrides Overrides.
	 * @return array<string, mixed>
	 */
	private function options( array $overrides = [] ): array {
		return array_merge(
			[
				'notice_enabled'          => true,
				'notice_display_product'  => true,
				'notice_display_header'   => false,
				'notice_display_footer'   => false,
				'notice_display_checkout' => true,
				'notice_display_email'    => true,
				'garan_enabled'           => true,
				'compliance_reviewed'     => true,
			],
			$overrides
		);
	}

	public function test_reviewed_default_setup_has_no_problem(): void {
		$this->assertSame( [], ComplianceCheck::problems( $this->options(), false ) );
	}

	public function test_fresh_install_is_not_reviewed(): void {
		$this->assertSame( [ ComplianceCheck::NOT_REVIEWED ], ComplianceCheck::problems( $this->options( [ 'compliance_reviewed' => false ] ), false ) );
	}

	public function test_notice_off(): void {
		$this->assertSame( [ ComplianceCheck::NOTICE_OFF ], ComplianceCheck::problems( $this->options( [ 'notice_enabled' => false ] ), false ) );
	}

	public function test_no_placement(): void {
		$opts = $this->options(
			[
				'notice_display_product'  => false,
				'notice_display_checkout' => false,
				'notice_display_email'    => false,
			]
		);
		$this->assertSame( [ ComplianceCheck::NOTICE_HIDDEN ], ComplianceCheck::problems( $opts, false ) );
	}

	public function test_checkout_off_and_garan_filter(): void {
		$this->assertSame(
			[ ComplianceCheck::CHECKOUT_OFF, ComplianceCheck::GARAN_FILTERED ],
			ComplianceCheck::problems( $this->options( [ 'notice_display_checkout' => false ] ), true )
		);
	}

	public function test_garan_off(): void {
		$this->assertSame( [ ComplianceCheck::GARAN_OFF ], ComplianceCheck::problems( $this->options( [ 'garan_enabled' => false ] ), false ) );
	}

	public function test_garan_off_hides_the_filter_warning(): void {
		$this->assertSame( [ ComplianceCheck::GARAN_OFF ], ComplianceCheck::problems( $this->options( [ 'garan_enabled' => false ] ), true ) );
	}

	public function test_garan_off_reason(): void {
		$message = ComplianceCheck::message( [ ComplianceCheck::GARAN_OFF ], '2026-09-27' );

		$this->assertStringEndsWith( 'A GARAN címke ki van kapcsolva, pedig az érintett termékeknél a rendelés gomb előtt kötelező.', $message );
	}

	public function test_snooze_lasts_thirty_days(): void {
		$now = 1790000000;
		$this->assertFalse( ComplianceCheck::is_snoozed( 0, $now ) );
		$this->assertTrue( ComplianceCheck::is_snoozed( $now - 29 * 86400, $now ) );
		$this->assertFalse( ComplianceCheck::is_snoozed( $now - 30 * 86400, $now ) );
	}

	public function test_message_depends_on_deadline(): void {
		$before = ComplianceCheck::message( [ ComplianceCheck::NOT_REVIEWED ], '2026-09-26' );
		$after  = ComplianceCheck::message( [ ComplianceCheck::CHECKOUT_OFF ], '2026-09-27' );

		$this->assertStringStartsWith( '2026. szeptember 27-től kötelező', $before );
		$this->assertStringEndsWith( 'A beállítások még nincsenek átnézve.', $before );
		$this->assertStringStartsWith( 'A harmonizált szavatossági tájékoztató 2026. szeptember 27. óta kötelező.', $after );
		$this->assertStringContainsString( 'A pénztári megjelenítés ki van kapcsolva.', $after );
		$this->assertStringContainsString( 'Jelenleg nem jelenik meg.', ComplianceCheck::message( [ ComplianceCheck::NOTICE_OFF ], '2026-10-01' ) );
	}
}
