<?php
/**
 * Renders individual sections of the case detail page.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Database\CaseItemRepository;
use LightweightPlugins\Elallas\Database\DocumentRepository;
use LightweightPlugins\Elallas\Database\EventRepository;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Models\WithdrawalCase;

/**
 * Section renderers for CaseDetailPage. Keeps the page class lean.
 */
final class CaseDetailSections {

	/**
	 * Summary section.
	 *
	 * @param WithdrawalCase $case Case.
	 * @return void
	 */
	public static function summary( WithdrawalCase $case ): void {
		$order_url = admin_url( 'post.php?post=' . $case->order_id . '&action=edit' );
		echo '<h2>' . esc_html__( 'Összefoglaló', 'elallas-for-woo' ) . '</h2>';
		echo '<table class="widefat striped"><tbody>';
		self::row( __( 'Ügyszám', 'elallas-for-woo' ), esc_html( $case->case_number ) );
		self::row( __( 'Rendelés', 'elallas-for-woo' ), '<a href="' . esc_url( $order_url ) . '">#' . esc_html( $case->order_number ) . '</a>' );
		self::row( __( 'Státusz', 'elallas-for-woo' ), esc_html( $case->status_label() ) );
		self::row( __( 'Beérkezett', 'elallas-for-woo' ), esc_html( (string) $case->submitted_at ) );
		self::row( __( 'Határidő', 'elallas-for-woo' ), esc_html( $case->deadline_label() ) );
		echo '</tbody></table>';
	}

	/**
	 * Customer declaration and case items.
	 *
	 * @param WithdrawalCase $case Case.
	 * @return void
	 */
	public static function items( WithdrawalCase $case ): void {
		echo '<h2>' . esc_html__( 'Vásárlói nyilatkozat és tételek', 'elallas-for-woo' ) . '</h2>';

		if ( null !== $case->customer_note && '' !== $case->customer_note ) {
			echo '<p>' . esc_html( $case->customer_note ) . '</p>';
		}

		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Termék', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'SKU', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Rendelt', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Elállt', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Jogosultság', 'elallas-for-woo' ) . '</th></tr></thead><tbody>';

		foreach ( CaseItemRepository::for_case( $case->id ) as $item ) {
			printf(
				'<tr><td>%s</td><td>%s</td><td>%d</td><td>%d</td><td>%s</td></tr>',
				esc_html( $item->product_name_snapshot ),
				esc_html( $item->sku_snapshot ),
				(int) $item->qty_ordered,
				(int) $item->qty_withdrawn,
				esc_html( $item->eligibility_flag )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Audit log section.
	 *
	 * @param WithdrawalCase $case Case.
	 * @return void
	 */
	public static function audit( WithdrawalCase $case ): void {
		echo '<h2>' . esc_html__( 'Eseménynapló', 'elallas-for-woo' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Időpont', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Típus', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Üzenet', 'elallas-for-woo' ) . '</th></tr></thead><tbody>';

		foreach ( EventRepository::for_case( $case->id ) as $event ) {
			printf(
				'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_html( (string) ( $event->created_at ?? '' ) ),
				esc_html( (string) ( $event->event_type ?? '' ) ),
				esc_html( (string) ( $event->message ?? '' ) )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Admin decision form.
	 *
	 * @param WithdrawalCase $case Case.
	 * @return void
	 */
	public static function decision_form( WithdrawalCase $case ): void {
		echo '<h2>' . esc_html__( 'Admin döntés', 'elallas-for-woo' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'elallas_change_status' );
		echo '<input type="hidden" name="action" value="elallas_change_status" />';
		echo '<input type="hidden" name="case_id" value="' . esc_attr( (string) $case->id ) . '" />';
		echo '<select name="new_status">';
		foreach ( CaseStatus::labels() as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $case->status, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select> ';
		submit_button( __( 'Státusz mentése', 'elallas-for-woo' ), 'primary', 'submit', false );
		echo '</form>';
	}

	/**
	 * Documents section with download links.
	 *
	 * @param WithdrawalCase $case Case.
	 * @return void
	 */
	public static function documents( WithdrawalCase $case ): void {
		echo '<h2>' . esc_html__( 'Dokumentumok', 'elallas-for-woo' ) . '</h2><ul>';

		foreach ( DocumentRepository::for_case( $case->id ) as $doc ) {
			$url = wp_nonce_url(
				add_query_arg(
					[
						'page'        => 'elallas-for-woo',
						'view'        => 'case',
						'case_id'     => $case->id,
						'download_doc' => (int) ( $doc->id ?? 0 ),
					],
					admin_url( 'admin.php' )
				),
				'elallas_download_doc'
			);
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( $url ),
				esc_html( (string) ( $doc->document_type ?? '' ) )
			);
		}

		echo '</ul>';
	}

	/**
	 * Render a single key/value table row.
	 *
	 * @param string $label Label.
	 * @param string $value Pre-escaped HTML value.
	 * @return void
	 */
	private static function row( string $label, string $value ): void {
		echo '<tr><th style="width:200px;">' . esc_html( $label ) . '</th><td>' . wp_kses_post( $value ) . '</td></tr>';
	}
}
