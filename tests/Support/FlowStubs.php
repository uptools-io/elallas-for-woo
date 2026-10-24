<?php
/**
 * Minimal WordPress / WooCommerce stand-ins for the customer flow tests.
 *
 * In-memory transients, orders and a tiny $wpdb double, so the domain and
 * front-end classes run without a WordPress install.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

// phpcs:ignoreFile -- Test stubs.

if ( ! class_exists( 'WC_Order' ) ) {
	class WC_Order {
		/** @var int */
		public $id = 0;
		/** @var string */
		public $billing_email = '';
		/** @var string */
		public $status = 'completed';
		/** @var int */
		public $customer_id = 0;

		public function __construct( int $id = 0, string $billing_email = '', string $status = 'completed' ) {
			$this->id            = $id;
			$this->billing_email = $billing_email;
			$this->status        = $status;
		}
		public function get_id() { return $this->id; }
		public function get_order_number() { return (string) $this->id; }
		public function get_billing_email() { return $this->billing_email; }
		public function get_status() { return $this->status; }
		public function get_customer_id() { return $this->customer_id; }
		public function get_date_created() { return null; }
		public function get_date_completed() { return null; }
		public function get_date_paid() { return null; }
		public function get_meta( $key = '' ) { return ''; }
		public function get_items() { return []; }
	}
}

/**
 * Mutable state behind the stubs below.
 */
final class Elallas_Flow_Stub {
	/** @var array<string, mixed> */
	public static $transients = [];
	/** @var array<int, WC_Order> */
	public static $orders = [];
	/** @var array<int, array<string, mixed>> */
	public static $cases = [];
	/** @var array<int, array{0: string, 1: array<string, mixed>}> */
	public static $updates = [];
	/** @var array<int, array{0: string, 1: array<string, mixed>}> */
	public static $inserts = [];
	/** @var array<int, string> */
	public static $actions = [];

	public static function reset(): void {
		self::$transients = [];
		self::$orders     = [];
		self::$cases      = [];
		self::$updates    = [];
		self::$inserts    = [];
		self::$actions    = [];
		$GLOBALS['wpdb']  = new Elallas_Flow_Wpdb();
	}
}

/**
 * Tiny $wpdb double: rows come from Elallas_Flow_Stub::$cases.
 */
final class Elallas_Flow_Wpdb {
	/** @var string */
	public $prefix = 'wp_';
	/** @var int */
	public $insert_id = 0;

	public function prepare( $query, ...$args ) {
		return [ $query, $args ];
	}
	public function get_row( $prepared ) {
		$id = (int) ( $prepared[1][0] ?? 0 );
		return isset( Elallas_Flow_Stub::$cases[ $id ] ) ? (object) Elallas_Flow_Stub::$cases[ $id ] : null;
	}
	public function get_results( $prepared ) {
		return [];
	}
	public function update( $table, $data, $where ) {
		Elallas_Flow_Stub::$updates[] = [ $table, $data ];
		return 1;
	}
	public function insert( $table, $data ) {
		Elallas_Flow_Stub::$inserts[] = [ $table, $data ];
		$this->insert_id = count( Elallas_Flow_Stub::$inserts );
		return 1;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return Elallas_Flow_Stub::$transients[ $key ] ?? false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) {
		Elallas_Flow_Stub::$transients[ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return $default;
	}
}
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		return array_merge( (array) $defaults, (array) $args );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( (string) $value );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $value ) {
		return strtolower( (string) preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $value ) );
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
	}
}
if ( ! function_exists( 'wc_get_order' ) ) {
	function wc_get_order( $id ) {
		return Elallas_Flow_Stub::$orders[ (int) $id ] ?? false;
	}
}
if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return false;
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 0;
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type ) {
		return '2026-10-24 12:00:00';
	}
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		Elallas_Flow_Stub::$actions[] = $hook;
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}
