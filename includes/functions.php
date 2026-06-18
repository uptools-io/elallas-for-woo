<?php
/**
 * Public API functions.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Models\WithdrawalCase;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a plugin option.
 *
 * @param string $key     Option key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function elallas_get_option( string $key, mixed $default = null ): mixed {
	return Options::get( $key, $default );
}

/**
 * Get a withdrawal case by ID.
 *
 * @param int $case_id Case ID.
 * @return WithdrawalCase|null
 */
function elallas_get_case( int $case_id ): ?WithdrawalCase {
	return CaseRepository::find( $case_id );
}

/**
 * Get all withdrawal cases for an order.
 *
 * @param int $order_id Order ID.
 * @return array<int, WithdrawalCase>
 */
function elallas_get_order_cases( int $order_id ): array {
	return CaseRepository::find_by_order( $order_id );
}

/**
 * Whether an order already has at least one withdrawal case.
 *
 * @param int $order_id Order ID.
 * @return bool
 */
function elallas_order_has_case( int $order_id ): bool {
	return ! empty( CaseRepository::find_by_order( $order_id ) );
}
