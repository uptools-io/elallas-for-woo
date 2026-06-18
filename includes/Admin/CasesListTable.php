<?php
/**
 * Withdrawal cases list table.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Models\WithdrawalCase;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Lists withdrawal cases with filters and bulk actions.
 */
final class CasesListTable extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'case',
				'plural'   => 'cases',
				'ajax'     => false,
			]
		);
	}

	/**
	 * Table columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return [
			'cb'          => '<input type="checkbox" />',
			'case_number' => __( 'Ügyszám', 'elallas-for-woo' ),
			'order'       => __( 'Rendelés', 'elallas-for-woo' ),
			'customer'    => __( 'Vásárló', 'elallas-for-woo' ),
			'status'      => __( 'Státusz', 'elallas-for-woo' ),
			'type'        => __( 'Típus', 'elallas-for-woo' ),
			'submitted'   => __( 'Beérkezett', 'elallas-for-woo' ),
			'deadline'    => __( 'Határidő', 'elallas-for-woo' ),
			'items'       => __( 'Tételek', 'elallas-for-woo' ),
			'last_event'  => __( 'Utolsó esemény', 'elallas-for-woo' ),
		];
	}

	/**
	 * Checkbox column.
	 *
	 * @param WithdrawalCase $item Case row.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="case[]" value="%d" />', (int) $item->id );
	}

	/**
	 * Case-number column.
	 *
	 * @param WithdrawalCase $item Case row.
	 * @return string
	 */
	public function column_case_number( WithdrawalCase $item ): string {
		return CaseColumns::case_number( $item );
	}

	/**
	 * Order column.
	 *
	 * @param WithdrawalCase $item Case row.
	 * @return string
	 */
	public function column_order( WithdrawalCase $item ): string {
		return CaseColumns::order( $item );
	}

	/**
	 * Default column dispatcher.
	 *
	 * @param WithdrawalCase $item        Case row.
	 * @param string         $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return CaseColumns::cell( $item, $column_name );
	}

	/**
	 * Bulk actions.
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions(): array {
		return [
			'mark_review'   => __( 'Manuális ellenőrzésre', 'elallas-for-woo' ),
			'mark_accepted' => __( 'Elfogadás', 'elallas-for-woo' ),
			'mark_rejected' => __( 'Elutasítás', 'elallas-for-woo' ),
			'mark_closed'   => __( 'Lezárás', 'elallas-for-woo' ),
			'delete'        => __( 'Törlés', 'elallas-for-woo' ),
		];
	}

	/**
	 * Status filter dropdown above the table.
	 *
	 * @param string $which Tablenav position.
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$current = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="alignleft actions"><select name="status">';
		echo '<option value="">' . esc_html__( 'Minden státusz', 'elallas-for-woo' ) . '</option>';
		foreach ( CaseStatus::labels() as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $current, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		submit_button( __( 'Szűrés', 'elallas-for-woo' ), '', 'filter_action', false );
		echo '</div>';
	}

	/**
	 * Prepare the items, applying filters, pagination, and bulk actions.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		CaseBulkActions::process( (string) $this->current_action(), 'bulk-' . $this->_args['plural'] );

		$per_page = 20;
		$paged    = $this->get_pagenum();
		$filters  = $this->read_filters();

		$result = CaseRepository::query( array_filter( $filters ), $paged, $per_page );

		$this->_column_headers = [ $this->get_columns(), [], [] ];
		$this->items           = $result['items'];

		$this->set_pagination_args(
			[
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			]
		);
	}

	/**
	 * Read GET filters (read-only listing — nonce not required).
	 *
	 * @return array<string, string>
	 */
	private function read_filters(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return [
			'status'          => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
			'deadline_status' => isset( $_GET['deadline_status'] ) ? sanitize_key( wp_unslash( $_GET['deadline_status'] ) ) : '',
			'withdrawal_type' => isset( $_GET['withdrawal_type'] ) ? sanitize_key( wp_unslash( $_GET['withdrawal_type'] ) ) : '',
			'search'          => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}
