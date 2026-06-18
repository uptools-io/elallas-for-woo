<?php
/**
 * Elállás case ability definitions for LW Site Manager.
 *
 * Registers read/write abilities against the WordPress Abilities API. Permission
 * callbacks prefer the Site Manager PermissionManager and fall back to a direct
 * manage_woocommerce capability check. Execute logic lives in CaseService.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\SiteManager;

/**
 * Registers elállás case abilities with the WordPress Abilities API.
 */
final class CaseAbilities {

	/**
	 * Site Manager PermissionManager instance, when available.
	 *
	 * @var object|null
	 */
	private static ?object $permissions = null;

	/**
	 * Register all elállás abilities.
	 *
	 * @param object|null $permissions Optional Site Manager PermissionManager.
	 * @return void
	 */
	public static function register( ?object $permissions = null ): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::$permissions = $permissions;

		foreach ( self::definitions() as $slug => $args ) {
			wp_register_ability( $slug, $args );
		}
	}

	/**
	 * All ability definitions keyed by ability slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function definitions(): array {
		$id = self::case_id_schema();

		return [
			'elallas/list-cases'         => [
				'label'               => __( 'List Withdrawal Cases', 'elallas-for-woo' ),
				'description'         => __( 'List elállás cases, optionally filtered by status and paged.', 'elallas-for-woo' ),
				'category'            => 'elallas',
				'execute_callback'    => [ CaseService::class, 'list_cases' ],
				'permission_callback' => self::permission(),
				'input_schema'        => [
					'type'       => 'object',
					'default'    => [],
					'properties' => [
						'status' => [ 'type' => 'string' ],
						'paged'  => [
							'type'    => 'integer',
							'default' => 1,
						],
					],
				],
				'output_schema'       => self::object_schema(
					[
						'cases' => [ 'type' => 'array' ],
						'total' => [ 'type' => 'integer' ],
					]
				),
				'meta'                => self::readonly_meta(),
			],
			'elallas/get-case'           => [
				'label'               => __( 'Get Withdrawal Case', 'elallas-for-woo' ),
				'description'         => __( 'Get a case with its items and audit events.', 'elallas-for-woo' ),
				'category'            => 'elallas',
				'execute_callback'    => [ CaseService::class, 'get_case' ],
				'permission_callback' => self::permission(),
				'input_schema'        => $id,
				'output_schema'       => self::object_schema(
					[
						'case'   => [ 'type' => 'object' ],
						'items'  => [ 'type' => 'array' ],
						'events' => [ 'type' => 'array' ],
					]
				),
				'meta'                => self::readonly_meta(),
			],
			'elallas/update-case-status' => [
				'label'               => __( 'Update Case Status', 'elallas-for-woo' ),
				'description'         => __( 'Change the status of a withdrawal case.', 'elallas-for-woo' ),
				'category'            => 'elallas',
				'execute_callback'    => [ CaseService::class, 'update_status' ],
				'permission_callback' => self::permission(),
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'case_id', 'status' ],
					'properties' => [
						'case_id' => [ 'type' => 'integer' ],
						'status'  => [ 'type' => 'string' ],
					],
				],
				'output_schema'       => self::object_schema( [ 'message' => [ 'type' => 'string' ] ] ),
				'meta'                => self::write_meta(),
			],
			'elallas/get-audit-log'      => [
				'label'               => __( 'Get Case Audit Log', 'elallas-for-woo' ),
				'description'         => __( 'Get the audit event log for a withdrawal case.', 'elallas-for-woo' ),
				'category'            => 'elallas',
				'execute_callback'    => [ CaseService::class, 'get_audit_log' ],
				'permission_callback' => self::permission(),
				'input_schema'        => $id,
				'output_schema'       => self::object_schema( [ 'events' => [ 'type' => 'array' ] ] ),
				'meta'                => self::readonly_meta(),
			],
		];
	}

	/**
	 * Input schema requiring only a case_id.
	 *
	 * @return array<string, mixed>
	 */
	private static function case_id_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'case_id' ],
			'properties' => [ 'case_id' => [ 'type' => 'integer' ] ],
		];
	}

	/**
	 * Build an object output schema with a leading success flag.
	 *
	 * @param array<string, mixed> $properties Additional properties.
	 * @return array<string, mixed>
	 */
	private static function object_schema( array $properties ): array {
		return [
			'type'       => 'object',
			'properties' => array_merge( [ 'success' => [ 'type' => 'boolean' ] ], $properties ),
		];
	}

	/**
	 * Build the permission callback (Site Manager manager, else manage_woocommerce).
	 *
	 * @return callable
	 */
	private static function permission(): callable {
		$manager = self::$permissions;

		if ( $manager && method_exists( $manager, 'callback' ) ) {
			return $manager->callback( 'can_manage_orders' );
		}

		return static fn(): bool => current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Read-only ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function readonly_meta(): array {
		return [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
	}

	/**
	 * Write ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function write_meta(): array {
		return [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => false,
			],
		];
	}
}
