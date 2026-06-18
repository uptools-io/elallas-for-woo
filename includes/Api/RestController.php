<?php
/**
 * REST API controller registration.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Api;

/**
 * Registers all REST API routes for the elallas-for-woo plugin.
 */
final class RestController {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	public const NAMESPACE = 'elallas-for-woo/v1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$identify = new IdentifyController();
		$cases    = new CasesController();
		$document = new DocumentController();

		register_rest_route(
			self::NAMESPACE,
			'/identify-order',
			[
				'methods'             => 'POST',
				'callback'            => [ $identify, 'handle' ],
				'permission_callback' => [ $identify, 'permission_public' ],
				'args'                => [
					'order_number' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'email'        => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/cases',
			[
				'methods'             => 'POST',
				'callback'            => [ $cases, 'create' ],
				'permission_callback' => [ $cases, 'permission_nonce' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/cases/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $cases, 'get' ],
				'permission_callback' => [ $cases, 'permission_admin' ],
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/cases/(?P<id>\d+)/confirm',
			[
				'methods'             => 'POST',
				'callback'            => [ $cases, 'confirm' ],
				'permission_callback' => [ $cases, 'permission_nonce' ],
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/cases/(?P<id>\d+)/status',
			[
				'methods'             => 'POST',
				'callback'            => [ $cases, 'change_status' ],
				'permission_callback' => [ $cases, 'permission_admin' ],
				'args'                => [
					'id'     => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
					'status' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/cases/(?P<id>\d+)/document',
			[
				'methods'             => 'GET',
				'callback'            => [ $document, 'get' ],
				'permission_callback' => [ $document, 'permission_admin' ],
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}
}
