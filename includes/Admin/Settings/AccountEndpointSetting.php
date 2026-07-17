<?php
/**
 * Withdrawal account-endpoint slug field for WooCommerce settings.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Frontend\MyAccountEndpoint;

/**
 * Surfaces the "Elállás" account-endpoint slug in
 * WooCommerce > Settings > Advanced > Account endpoints, next to WooCommerce's
 * own endpoint slugs, so the merchant edits every "My account" URL slug in one
 * place. WooCommerce persists the value and queues a rewrite flush on save.
 */
final class AccountEndpointSetting {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_settings_advanced', [ $this, 'add_field' ] );
	}

	/**
	 * Insert the slug field into the "Account endpoints" group.
	 *
	 * The group is delimited by a `sectionend` whose id is
	 * `account_endpoint_options`; the field is spliced in just before it. Only
	 * that section carries the marker, so every other Advanced section (and
	 * older/newer WooCommerce layouts without it) is returned untouched.
	 *
	 * @param array<int, array<string, mixed>> $settings WooCommerce advanced settings.
	 * @return array<int, array<string, mixed>>
	 */
	public function add_field( array $settings ): array {
		$insert_at = null;

		foreach ( $settings as $index => $field ) {
			if ( 'sectionend' === ( $field['type'] ?? '' ) && 'account_endpoint_options' === ( $field['id'] ?? '' ) ) {
				$insert_at = $index;
				break;
			}
		}

		if ( null === $insert_at ) {
			return $settings;
		}

		$field = [
			'title'    => __( 'Elállás', 'elallas-for-woo' ),
			'desc'     => __( 'Az „Elállás" fiók-végpont URL-részlete (pl. elallasaim). Mentés után a fiók menü hivatkozása automatikusan az új címre mutat.', 'elallas-for-woo' ),
			'id'       => MyAccountEndpoint::SLUG_OPTION,
			'type'     => 'text',
			'default'  => 'withdrawals',
			'desc_tip' => true,
		];

		array_splice( $settings, $insert_at, 0, [ $field ] );

		return $settings;
	}
}
