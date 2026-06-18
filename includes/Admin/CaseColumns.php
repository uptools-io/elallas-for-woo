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
		$url = admin_url( 'post.php?post=' . $case->order_id . '&action=edit' );
		return sprintf( '<a href="%s">#%s</a>', esc_url( $url ), esc_html( $case->order_number ) );
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
				return $case->customer_id > 0
					? esc_html( '#' . $case->customer_id )
					: esc_html__( 'Vendég', 'elallas-for-woo' );
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
