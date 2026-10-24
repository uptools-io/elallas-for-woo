<?php
/**
 * OrderAdapter::email_matches() tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

require_once dirname( __DIR__ ) . '/Support/FlowStubs.php';

use Elallas_Flow_Stub;
use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Domain\EligibilityChecker;
use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * @covers \LightweightPlugins\Elallas\Woo\OrderAdapter
 */
final class OrderEmailMatchTest extends TestCase {

	protected function setUp(): void {
		Elallas_Flow_Stub::reset();
	}

	public function test_match_normalises_address(): void {
		$order = new \WC_Order( 5, 'Buyer@Example.com' );

		$this->assertTrue( OrderAdapter::email_matches( $order, '  buyer@example.COM ' ) );
	}

	public function test_match_different_address(): void {
		$order = new \WC_Order( 5, 'buyer@example.com' );

		$this->assertFalse( OrderAdapter::email_matches( $order, 'other@example.com' ) );
	}

	/**
	 * @dataProvider pairs
	 */
	public function test_match_rules( string $billing, string $candidate ): void {
		$this->assertFalse( OrderAdapter::email_matches( new \WC_Order( 5, $billing ), $candidate ) );
	}

	/**
	 * @return array<int, array{0: string, 1: string}>
	 */
	public static function pairs(): array {
		return [
			[ '', '' ],
			[ '  ', ' ' ],
			[ 'buyer@example.com', '' ],
			[ '', 'buyer@example.com' ],
			[ 'not-an-email', 'not-an-email' ],
			[ 'buyer@', 'buyer@' ],
		];
	}

	public function test_eligibility_rules(): void {
		$result = ( new EligibilityChecker() )->check( new \WC_Order( 5, '' ), '' );

		$this->assertFalse( $result->eligible );
	}
}
