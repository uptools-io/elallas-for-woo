<?php
/**
 * Column rendering helpers for the cases list table.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Database\CaseItemRepository;
use LightweightPlugins\Elallas\Database\EventRepository;
use LightweightPlugins\Elallas\Models\WithdrawalCase;
use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * Renders individual list-table cells. Keeps CasesListTable lean.
 */
final class CaseColumns {

	/**
	 * Case-number cell linking to the detail view.
	 *
	 * @param WithdrawalCase $case Case row.
	 * @return string
	 */
	public static function case_number( WithdrawalCase $case ): string {
		$url = add_query_arg(
			[
				'page'    => 'elallas-for-woo',
				'view'    => 'case',
				'case_id' => $case->id,
			],
			admin_url( 'admin.php' )
		);

		return sprintf( '<a href="%s"><strong>%s</strong></a>', esc_url( $url ), esc_html( $case->case_number ) );
	}

	/**
	 * Order cell linking to the order edit screen.
	 *
	 * @param WithdrawalCase $case Case row.
	 * @return string
	 */
	public static function order( WithdrawalCase $case ): string {
		// HPOS-safe: get_edit_order_url() resolves to the correct order screen under
		// both legacy CPT and High-Performance Order Storage (a hand-built post.php
		// link points at a non-existent post when HPOS is authoritative).
		$order = OrderAdapter::get_order( $case->order_id );
		$url   = $order ? $order->get_edit_order_url() : '';

		return '' !== $url
			? sprintf( '<a href="%s">#%s</a>', esc_url( $url ), esc_html( $case->order_number ) )
			: sprintf( '#%s', esc_html( $case->order_number ) );
	}

	/**
	 * Default cell for simple columns.
	 *
	 * @param WithdrawalCase $case   Case row.
	 * @param string         $column Column key.
	 * @return string
	 */
	public static function cell( WithdrawalCase $case, string $column ): string {
		switch ( $column ) {
			case 'customer':
				return self::customer( $case );
			case 'status':
				return esc_html( $case->status_label() );
			case 'type':
				return esc_html( $case->withdrawal_type );
			case 'submitted':
				return esc_html( (string) $case->submitted_at );
			case 'deadline':
				return esc_html( $case->deadline_label() );
			case 'items':
				return (string) count( CaseItemRepository::for_case( $case->id ) );
			case 'last_event':
				return self::last_event( $case->id );
			default:
				return '';
		}
	}

	/**
	 * Customer cell: a linked username to the user profile for registered
	 * customers (falling back to the raw name / id when not editable or the
	 * user was deleted), or "Vendég" for guest orders.
	 *
	 * @param WithdrawalCase $case Case row.
	 * @return string
	 */
	private static function customer( WithdrawalCase $case ): string {
		$customer_id = (int) $case->customer_id;

		if ( $customer_id <= 0 ) {
			return esc_html__( 'Vendég', 'elallas-for-woo' );
		}

		$user = get_userdata( $customer_id );

		if ( ! $user ) {
			// Account was deleted since the case was filed.
			return esc_html( '#' . $customer_id );
		}

		$name = '' !== $user->display_name ? $user->display_name : $user->user_login;
		$link = get_edit_user_link( $customer_id );

		return '' !== $link
			? sprintf( '<a href="%s">%s</a>', esc_url( $link ), esc_html( $name ) )
			: esc_html( $name );
	}

	/**
	 * Latest event message for a case.
	 *
	 * @param int $case_id Case ID.
	 * @return string
	 */
	private static function last_event( int $case_id ): string {
		$events = EventRepository::for_case( $case_id );
		$last   = end( $events );

		return $last ? esc_html( (string) ( $last->message ?? '' ) ) : '&mdash;';
	}
}
