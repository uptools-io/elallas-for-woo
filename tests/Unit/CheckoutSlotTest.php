<?php
/**
 * CheckoutSlot::is_block_checkout() regression test with minimal WP stubs.
 *
 * The slot must be decided by the CURRENT request: a classic
 * [woocommerce_checkout] page is a checkout too, even when the configured
 * checkout page uses the block, and must not get the hidden slot template.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace {
	if ( ! class_exists( 'WP_Post' ) ) {
		// phpcs:ignore -- Test stub.
		final class WP_Post {
			/** @var int */
			public $ID = 0;
			/** @var string */
			public $post_content = '';
		}
	}

	/**
	 * Mutable stub state for the WP functions below.
	 */
	final class Elallas_Slot_Stub {
		/** @var array<string, mixed> */
		public static $state = [];
	}

	if ( ! function_exists( 'is_checkout' ) ) {
		function is_checkout(): bool {
			return (bool) Elallas_Slot_Stub::$state['is_checkout'];
		}
	}
	if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
		function is_wc_endpoint_url( string $endpoint = '' ): bool {
			return in_array( $endpoint, (array) Elallas_Slot_Stub::$state['endpoints'], true );
		}
	}
	if ( ! function_exists( 'get_queried_object_id' ) ) {
		function get_queried_object_id(): int {
			return (int) Elallas_Slot_Stub::$state['queried'];
		}
	}
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		function wc_get_page_id( string $page ): int {
			return (int) Elallas_Slot_Stub::$state['checkout_page'];
		}
	}
	if ( ! function_exists( 'get_post' ) ) {
		function get_post( $id ) {
			$pages = (array) Elallas_Slot_Stub::$state['pages'];
			if ( ! isset( $pages[ (int) $id ] ) ) {
				return null;
			}
			$post               = new WP_Post();
			$post->ID           = (int) $id;
			$post->post_content = (string) $pages[ (int) $id ];
			return $post;
		}
	}
	if ( ! function_exists( 'has_block' ) ) {
		function has_block( string $name, $post = null ): bool {
			return $post instanceof WP_Post && false !== strpos( $post->post_content, '<!-- wp:' . $name );
		}
	}
}

namespace LightweightPlugins\Elallas\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use LightweightPlugins\Elallas\Compliance\CheckoutSlot;

	/**
	 * @covers \LightweightPlugins\Elallas\Compliance\CheckoutSlot::is_block_checkout
	 */
	final class CheckoutSlotTest extends TestCase {

		private const BLOCK   = '<!-- wp:woocommerce/checkout --><div></div><!-- /wp:woocommerce/checkout -->';
		private const CLASSIC = '[woocommerce_checkout]';

		/**
		 * @param array<string, mixed> $state Stub state.
		 */
		private static function state( array $state ): void {
			\Elallas_Slot_Stub::$state = array_merge(
				[
					'is_checkout'   => true,
					'endpoints'     => [],
					'queried'       => 7,
					'checkout_page' => 7,
					'pages'         => [
						7  => self::BLOCK,
						15 => self::CLASSIC,
					],
				],
				$state
			);
		}

		public function test_block_checkout_page(): void {
			self::state( [] );
			$this->assertTrue( CheckoutSlot::is_block_checkout() );
		}

		public function test_classic_page_while_configured_page_is_block(): void {
			self::state( [ 'queried' => 15 ] );
			$this->assertFalse( CheckoutSlot::is_block_checkout() );
		}

		public function test_order_pay_and_order_received_endpoints(): void {
			self::state( [ 'endpoints' => [ 'order-pay' ] ] );
			$this->assertFalse( CheckoutSlot::is_block_checkout() );
			self::state( [ 'endpoints' => [ 'order-received' ] ] );
			$this->assertFalse( CheckoutSlot::is_block_checkout() );
		}

		public function test_not_a_checkout(): void {
			self::state( [ 'is_checkout' => false ] );
			$this->assertFalse( CheckoutSlot::is_block_checkout() );
		}

		public function test_no_queried_post_falls_back_to_the_configured_page(): void {
			self::state( [ 'queried' => 0 ] );
			$this->assertTrue( CheckoutSlot::is_block_checkout() );
		}
	}
}
