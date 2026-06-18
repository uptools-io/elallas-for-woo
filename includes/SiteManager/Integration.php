<?php
/**
 * LW Site Manager integration.
 *
 * Registers elállás case abilities. Prefers the LW Site Manager bridge (which
 * carries a PermissionManager instance) when active; otherwise falls back to the
 * direct Abilities API hooks so abilities still work with only the Abilities API
 * present (WP 6.9+ or the feature plugin). Safe to call when neither is active.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\SiteManager;

/**
 * Hooks ability registration into both Site Manager and the Abilities API.
 */
final class Integration {

	/**
	 * Ability category identifier.
	 */
	private const CATEGORY_ID = 'elallas';

	/**
	 * Register hooks. Safe when neither Site Manager nor Abilities API is active.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Bridge: prefer Site Manager (carries its PermissionManager).
		add_action( 'lw_site_manager_register_categories', [ self::class, 'register_category' ] );
		add_action( 'lw_site_manager_register_abilities', [ self::class, 'register_via_site_manager' ] );

		// Direct fallback when Site Manager is not active. Priority 20 runs after the bridge.
		add_action( 'wp_abilities_api_categories_init', [ self::class, 'maybe_register_category_direct' ], 20 );
		add_action( 'wp_abilities_api_init', [ self::class, 'maybe_register_abilities_direct' ], 20 );
	}

	/**
	 * Register the elállás ability category.
	 *
	 * @return void
	 */
	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY_ID,
			[
				'label'       => __( 'Elállás (Withdrawal)', 'elallas-for-woo' ),
				'description' => __( 'WooCommerce withdrawal/elállás case management abilities.', 'elallas-for-woo' ),
			]
		);
	}

	/**
	 * Register abilities via the Site Manager bridge (uses its PermissionManager).
	 *
	 * @param object $permissions PermissionManager instance from Site Manager.
	 * @return void
	 */
	public static function register_via_site_manager( object $permissions ): void {
		CaseAbilities::register( $permissions );
	}

	/**
	 * Register the category directly when Site Manager has not done so.
	 *
	 * @return void
	 */
	public static function maybe_register_category_direct(): void {
		if ( did_action( 'lw_site_manager_register_categories' ) > 0 ) {
			return;
		}

		self::register_category();
	}

	/**
	 * Register abilities directly when Site Manager has not done so.
	 *
	 * @return void
	 */
	public static function maybe_register_abilities_direct(): void {
		if ( did_action( 'lw_site_manager_register_abilities' ) > 0 ) {
			return;
		}

		CaseAbilities::register( null );
	}
}
