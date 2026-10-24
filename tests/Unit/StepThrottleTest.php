<?php
/**
 * Throttling of the front-end withdrawal steps.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

require_once dirname( __DIR__ ) . '/Support/FlowStubs.php';

use Elallas_Flow_Stub;
use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Frontend\StepProcessor;

/**
 * @covers \LightweightPlugins\Elallas\Frontend\StepProcessor
 * @covers \LightweightPlugins\Elallas\Security\RateLimiter
 */
final class StepThrottleTest extends TestCase {

	protected function setUp(): void {
		Elallas_Flow_Stub::reset();
		Elallas_Flow_Stub::$orders[5] = new \WC_Order( 5, 'buyer@example.com' );
		$this->post( '5' );
		$this->from_ip( '192.0.2.1' );
	}

	protected function tearDown(): void {
		$_POST = [];
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	private function post( string $order_number ): void {
		$_POST = [
			'order_number' => $order_number,
			'email'        => 'buyer@example.com',
		];
	}

	private function from_ip( string $ip ): void {
		$_SERVER['REMOTE_ADDR'] = $ip;
	}

	private function order_count( int $order_id ): int {
		return (int) ( Elallas_Flow_Stub::$transients[ 'lw_elallas_rlg_' . md5( 'order_' . $order_id ) ] ?? 0 );
	}

	public function test_full_flow(): void {
		$steps = new StepProcessor();

		$this->assertSame( 'select', $steps->identify()['view'] );
		$this->assertSame( 'select', $steps->select()['view'] );
		$steps->confirm();

		$this->assertSame( 3, $this->order_count( 5 ) );
	}

	public function test_client_limit(): void {
		$steps = new StepProcessor();

		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertNotSame( 'denied', $steps->select()['view'], "attempt {$i}" );
		}

		$this->assertSame( 'denied', $steps->select()['view'] );
	}

	public function test_order_limit_key(): void {
		$steps = new StepProcessor();

		$this->post( '#5' );
		$steps->identify();
		$this->post( '05' );
		$steps->identify();

		$this->assertSame( 2, $this->order_count( 5 ) );
		$this->assertArrayNotHasKey( 'lw_elallas_rlg_' . md5( 'order_#5' ), Elallas_Flow_Stub::$transients );
		$this->assertArrayNotHasKey( 'lw_elallas_rlg_' . md5( 'order_05' ), Elallas_Flow_Stub::$transients );
	}

	public function test_order_limit(): void {
		$steps = new StepProcessor();

		foreach ( [ '192.0.2.1', '192.0.2.2' ] as $ip ) {
			$this->from_ip( $ip );
			for ( $i = 0; $i < 5; $i++ ) {
				$steps->identify();
				$steps->select();
			}
		}

		$this->assertSame( 20, $this->order_count( 5 ) );

		$this->from_ip( '192.0.2.3' );
		$this->assertSame( 'denied', $steps->identify()['view'] );
	}
}
