<?php
/**
 * Admin-post action handlers for withdrawal cases.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Domain\CaseService;
use LightweightPlugins\Elallas\Models\CaseStatus;

/**
 * Handles admin write actions (status change, CSV export).
 */
final class CaseActions {

	/**
	 * Constructor — registers admin-post hooks.
	 */
	public function __construct() {
		add_action( 'admin_post_elallas_change_status', [ $this, 'change_status' ] );
		add_action( 'admin_post_elallas_export_csv', [ $this, 'export_csv' ] );
	}

	/**
	 * Handle a case status change from the detail page.
	 *
	 * @return void
	 */
	public function change_status(): void {
		check_admin_referer( 'elallas_change_status' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'elallas-for-woo' ) );
		}

		$case_id    = isset( $_POST['case_id'] ) ? absint( $_POST['case_id'] ) : 0;
		$new_status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';
		$message    = isset( $_POST['status_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['status_message'] ) ) : '';

		if ( $case_id > 0 && CaseStatus::is_valid( $new_status ) ) {
			( new CaseService() )->change_status( $case_id, $new_status, get_current_user_id(), $message );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'elallas-for-woo',
					'view'    => 'case',
					'case_id' => $case_id,
					'updated' => 1,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Stream a CSV export of cases matching the current filters.
	 *
	 * @return void
	 */
	public function export_csv(): void {
		check_admin_referer( 'elallas_export_csv' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'elallas-for-woo' ) );
		}

		$filters = [
			'status'          => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
			'deadline_status' => isset( $_GET['deadline_status'] ) ? sanitize_key( wp_unslash( $_GET['deadline_status'] ) ) : '',
			'withdrawal_type' => isset( $_GET['withdrawal_type'] ) ? sanitize_key( wp_unslash( $_GET['withdrawal_type'] ) ) : '',
			'search'          => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		];

		$result = CaseRepository::query( array_filter( $filters ), 1, 10000 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=elallas-cases-' . gmdate( 'Y-m-d' ) . '.csv' );

		$this->stream_rows( $result['items'] );
		exit;
	}

	/**
	 * Write the CSV header and case rows to the output stream.
	 *
	 * @param array<int, \LightweightPlugins\Elallas\Models\WithdrawalCase> $cases Cases.
	 * @return void
	 */
	private function stream_rows( array $cases ): void {
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [ 'case_number', 'order_number', 'status', 'withdrawal_type', 'submitted_at', 'deadline_status' ] );

		foreach ( $cases as $case ) {
			fputcsv(
				$out,
				[
					$case->case_number,
					$case->order_number,
					$case->status,
					$case->withdrawal_type,
					(string) $case->submitted_at,
					$case->deadline_status,
				]
			);
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the php://output stream used to stream the CSV export; WP_Filesystem cannot write to output.
	}
}
