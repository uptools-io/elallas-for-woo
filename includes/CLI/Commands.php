<?php
/**
 * WP-CLI commands.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\CLI;

use WP_CLI;
use WP_CLI\Utils;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Database\CaseItemRepository;
use LightweightPlugins\Elallas\Database\EventRepository;
use LightweightPlugins\Elallas\Domain\CaseService;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Cron\RetentionCleaner;
use LightweightPlugins\Elallas\Pdf\DocumentService;

/**
 * Manage WooCommerce withdrawal (elállás) cases from the command line.
 */
final class Commands {

	/**
	 * Lists withdrawal cases.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Filter by case status.
	 *
	 * [--deadline=<deadline>]
	 * : Filter by deadline status (within|expired|unknown).
	 *
	 * [--format=<format>]
	 * : Output format: table, csv, json or count. Default: table.
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function list( array $args, array $assoc_args ): void {
		$filters = array_filter(
			[
				'status'          => $assoc_args['status'] ?? '',
				'deadline_status' => $assoc_args['deadline'] ?? '',
			]
		);

		$result = CaseRepository::query( $filters, 1, 5000 );
		$rows   = array_map(
			static fn( $case ) => [
				'id'        => $case->id,
				'case'      => $case->case_number,
				'order'     => $case->order_number,
				'status'    => $case->status,
				'type'      => $case->withdrawal_type,
				'deadline'  => $case->deadline_status,
				'submitted' => (string) $case->submitted_at,
			],
			$result['items']
		);

		Utils\format_items( $assoc_args['format'] ?? 'table', $rows, [ 'id', 'case', 'order', 'status', 'type', 'deadline', 'submitted' ] );
	}

	/**
	 * Shows a single case with its items and audit log.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The case ID.
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @return void
	 */
	public function get( array $args ): void {
		$case = CaseRepository::find( (int) ( $args[0] ?? 0 ) );

		if ( null === $case ) {
			WP_CLI::error( 'Nincs ilyen ügy.' );
		}

		WP_CLI::log( sprintf( '%s | rendelés #%s | %s | %s', $case->case_number, $case->order_number, CaseStatus::label( $case->status ), (string) $case->submitted_at ) );

		foreach ( CaseItemRepository::for_case( $case->id ) as $item ) {
			WP_CLI::log( sprintf( '  - %s x%d (%s)', $item->product_name_snapshot, $item->qty_withdrawn, $item->line_total_snapshot ) );
		}

		foreach ( EventRepository::for_case( $case->id ) as $event ) {
			WP_CLI::log( sprintf( '  · [%s] %s — %s', $event->created_at, $event->event_type, (string) $event->message ) );
		}
	}

	/**
	 * Changes a case status.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The case ID.
	 *
	 * <status>
	 * : The new status (e.g. accepted, rejected, closed).
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @return void
	 */
	public function status( array $args ): void {
		$id     = (int) ( $args[0] ?? 0 );
		$status = (string) ( $args[1] ?? '' );

		if ( ! CaseStatus::is_valid( $status ) ) {
			WP_CLI::error( 'Érvénytelen státusz. Engedélyezett: ' . implode( ', ', CaseStatus::all() ) );
		}

		if ( ( new CaseService() )->change_status( $id, $status ) ) {
			WP_CLI::success( sprintf( '#%d → %s', $id, $status ) );
		} else {
			WP_CLI::error( 'A státusz módosítása sikertelen (nem létező ügy vagy azonos státusz).' );
		}
	}

	/**
	 * Shows case counts grouped by status.
	 *
	 * @return void
	 */
	public function stats(): void {
		$rows = [];

		foreach ( CaseRepository::count_by_status() as $status => $count ) {
			$rows[] = [
				'status' => $status,
				'label'  => CaseStatus::label( $status ),
				'count'  => $count,
			];
		}

		Utils\format_items( 'table', $rows, [ 'status', 'label', 'count' ] );
	}

	/**
	 * Generates (or regenerates) the PDF statement for a case.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The case ID.
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @return void
	 */
	public function pdf( array $args ): void {
		$doc_id = DocumentService::generate( (int) ( $args[0] ?? 0 ) );

		if ( 0 === $doc_id ) {
			WP_CLI::error( 'A PDF generálása sikertelen (kapcsold be a PDF-et, vagy ellenőrizd az ügyet).' );
		}

		WP_CLI::success( 'PDF: ' . DocumentService::get_file_path( $doc_id ) );
	}

	/**
	 * Runs the data-retention anonymization now.
	 *
	 * @return void
	 */
	public function cleanup(): void {
		( new RetentionCleaner() )->run();
		WP_CLI::success( 'A megőrzési idő szerinti anonimizálás lefutott.' );
	}
}
