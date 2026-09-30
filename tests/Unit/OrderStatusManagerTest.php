<?php
/**
 * OrderStatusManager tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Woo\OrderStatusManager;

/**
 * @covers \LightweightPlugins\Elallas\Woo\OrderStatusManager
 */
final class OrderStatusManagerTest extends TestCase {

	/**
	 * Both order storages keep the status in a varchar(20) column
	 * (wp_posts.post_status, wc_orders.status, wc_order_stats.status).
	 */
	private const STATUS_COLUMN_LENGTH = 20;

	public function test_order_status_slugs_fit_the_status_column(): void {
		foreach ( array_keys( OrderStatusManager::statuses() ) as $slug ) {
			$this->assertLessThanOrEqual( self::STATUS_COLUMN_LENGTH, strlen( $slug ), $slug );
			$this->assertStringStartsWith( 'wc-', $slug );
		}
	}

	public function test_every_case_status_maps_to_a_registered_order_status(): void {
		$registered = OrderStatusManager::statuses();

		foreach ( CaseStatus::all() as $case_status ) {
			$target = OrderStatusManager::map( $case_status );

			if ( '' !== $target ) {
				$this->assertArrayHasKey( $target, $registered, $case_status );
			}
		}
	}

	public function test_statuses_truncated_by_older_versions_resolve_to_registered_ones(): void {
		// Up to 1.1.0 these slugs were registered; MySQL cut them to 20 chars under HPOS.
		foreach ( [ 'wc-withdrawal-requested', 'wc-withdrawal-accepted' ] as $old_slug ) {
			$stored   = substr( $old_slug, 0, self::STATUS_COLUMN_LENGTH );
			$resolved = OrderStatusManager::LEGACY_SLUGS[ $stored ] ?? $stored;

			$this->assertArrayHasKey( $resolved, OrderStatusManager::statuses(), $stored );
		}
	}
}
