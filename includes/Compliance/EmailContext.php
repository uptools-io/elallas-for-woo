<?php
/**
 * Tracks the WooCommerce e-mail currently being rendered.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * Remembers the id of the WC e-mail whose order details are being rendered,
 * so hooks without an e-mail argument (e.g. `woocommerce_order_item_meta_end`)
 * can tell a customer e-mail from an admin one.
 *
 * `did_action( 'woocommerce_email_header' )` is not usable for this: it stays
 * true for the rest of the request and cannot tell the recipients apart.
 */
final class EmailContext {

	/**
	 * Customer order e-mails that carry the compliance output by default.
	 *
	 * @var array<int, string>
	 */
	public const CUSTOMER_EMAIL_IDS = [
		'customer_processing_order',
		'customer_completed_order',
		'customer_on_hold_order',
		'customer_invoice',
	];

	/**
	 * WooCommerce e-mail template actions: while one runs, output goes to an e-mail.
	 *
	 * @var array<int, string>
	 */
	private const EMAIL_ACTIONS = [
		'woocommerce_email_order_details',
		'woocommerce_email_before_order_table',
		'woocommerce_email_after_order_table',
		'woocommerce_email_order_meta',
		'woocommerce_email_customer_details',
	];

	/**
	 * Id of the e-mail being rendered ('' outside e-mails).
	 *
	 * @var string
	 */
	private static string $current = '';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_email_order_details', [ self::class, 'capture' ], 1, 4 );
		add_action( 'woocommerce_email_footer', [ self::class, 'reset' ], 99 );
		add_action( 'woocommerce_email_sent', [ self::class, 'reset' ] );
	}

	/**
	 * Capture the e-mail id at the start of the order-details block.
	 *
	 * @param mixed $order         Order (unused).
	 * @param mixed $sent_to_admin Sent to admin (unused).
	 * @param mixed $plain_text    Plain text (unused).
	 * @param mixed $email         WC_Email instance.
	 * @return void
	 */
	public static function capture( $order = null, $sent_to_admin = false, $plain_text = false, $email = null ): void {
		self::set( is_object( $email ) && isset( $email->id ) ? (string) $email->id : '' );
	}

	/**
	 * Set the current e-mail id.
	 *
	 * @param string $id E-mail id.
	 * @return void
	 */
	public static function set( string $id ): void {
		self::$current = $id;
	}

	/**
	 * Forget the current e-mail (plain e-mails have no footer action, hence also on send).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$current = '';
	}

	/**
	 * Id of the e-mail being rendered, '' outside e-mails.
	 *
	 * @return string
	 */
	public static function current(): string {
		return self::$current;
	}

	/**
	 * Whether an e-mail id belongs to a customer order e-mail.
	 *
	 * @param string $id E-mail id.
	 * @return bool
	 */
	public static function is_customer_email( string $id ): bool {
		if ( '' === $id ) {
			return false;
		}

		/**
		 * Filter the customer e-mail ids that carry the notice and the GARAN text.
		 *
		 * @param array<int, string> $ids E-mail ids.
		 */
		$ids = apply_filters( 'elallas_compliance_email_ids', self::CUSTOMER_EMAIL_IDS );

		return is_array( $ids ) && in_array( $id, $ids, true );
	}

	/**
	 * Whether any e-mail is being rendered right now (also e-mails rendered
	 * outside the captured order-details block, e.g. by third-party mailers):
	 * a captured id, a running WC e-mail action, or an open e-mail header.
	 *
	 * @return bool
	 */
	public static function rendering_email(): bool {
		$doing = false;
		foreach ( self::EMAIL_ACTIONS as $action ) {
			if ( doing_action( $action ) ) {
				$doing = true;
				break;
			}
		}

		return self::is_email_render( self::$current, $doing, did_action( 'woocommerce_email_header' ), did_action( 'woocommerce_email_footer' ) );
	}

	/**
	 * Decision behind rendering_email() (pure).
	 *
	 * @param string $current Captured e-mail id.
	 * @param bool   $doing   Whether a WC e-mail action is running.
	 * @param int    $headers did_action( 'woocommerce_email_header' ).
	 * @param int    $footers did_action( 'woocommerce_email_footer' ).
	 * @return bool
	 */
	public static function is_email_render( string $current, bool $doing, int $headers, int $footers ): bool {
		return '' !== $current || $doing || $headers > $footers;
	}

	/**
	 * Whether a customer order e-mail is being rendered right now.
	 *
	 * @return bool
	 */
	public static function in_customer_email(): bool {
		return self::is_customer_email( self::$current );
	}
}
