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
use LightweightPlugins\Elallas\Pdf\DocumentService;
use LightweightPlugins\Elallas\Security\Encryption;
use LightweightPlugins\Elallas\Database\EventRepository;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Models\CaseItem;
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

		$bank = ( null !== $case->bank_account_encrypted && '' !== $case->bank_account_encrypted )
			? Encryption::decrypt( (string) $case->bank_account_encrypted )
			: '';
		if ( '' !== $bank ) {
			self::row( __( 'Bankszámlaszám', 'elallas-for-woo' ), esc_html( $bank ) );
		}

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
			echo '<p><strong>' . esc_html__( 'Vásárló megjegyzése:', 'elallas-for-woo' ) . '</strong></p>';
			echo '<blockquote style="margin:0 0 16px;padding:8px 12px;border-left:4px solid #c3c4c7;background:#f6f7f7;">'
				. esc_html( $case->customer_note ) . '</blockquote>';
		}

		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Termék', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'SKU', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Rendelt', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Elállt', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Jogosultság', 'elallas-for-woo' ) . '</th></tr></thead><tbody>';

		foreach ( CaseItemRepository::for_case( $case->id ) as $item ) {
			echo '<tr><td>' . wp_kses_post( self::product_cell( $item ) ) . '</td>'
				. '<td>' . esc_html( $item->sku_snapshot ) . '</td>'
				. '<td>' . (int) $item->qty_ordered . '</td>'
				. '<td>' . (int) $item->qty_withdrawn . '</td>'
				. '<td>' . wp_kses_post( self::eligibility_cell( $item ) ) . '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Product name cell, linked to the product editor when possible.
	 *
	 * @param CaseItem $item Case item.
	 * @return string Escaped HTML.
	 */
	private static function product_cell( CaseItem $item ): string {
		$name = esc_html( $item->product_name_snapshot );
		$edit = $item->product_id > 0 ? get_edit_post_link( $item->product_id ) : null;

		if ( null !== $edit && '' !== $edit ) {
			return '<a href="' . esc_url( $edit ) . '" target="_blank" rel="noopener">' . $name . '</a>';
		}

		return $name;
	}

	/**
	 * Eligibility cell: translated flag, with the exclusion reason when excepted.
	 *
	 * @param CaseItem $item Case item.
	 * @return string Escaped HTML.
	 */
	private static function eligibility_cell( CaseItem $item ): string {
		$label = esc_html( $item->eligibility_label() );

		if ( ! $item->is_excepted() ) {
			return $label;
		}

		$out = '<span style="color:#b32d2e;font-weight:600;">' . $label . '</span>';

		if ( '' !== $item->eligibility_note ) {
			$out .= '<br /><span class="description">' . esc_html( $item->eligibility_note ) . '</span>';
		}

		return $out;
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
			// Served by DownloadHandler (?elallas_doc=ID): admins are authorised via the
			// manage_woocommerce capability, so no token is needed here.
			$url = add_query_arg( 'elallas_doc', (int) ( $doc->id ?? 0 ), home_url( '/' ) );
			printf(
				'<li><a href="%s" target="_blank" rel="noopener">%s</a></li>',
				esc_url( $url ),
				esc_html( DocumentService::type_label( (string) ( $doc->document_type ?? '' ) ) )
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
