<?php
/**
 * "Record refund in WooCommerce" section of the case detail page.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Domain\RefundCalculator;
use LightweightPlugins\Elallas\Integrations\Invoicing;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Models\WithdrawalCase;
use LightweightPlugins\Elallas\Woo\OrderAdapter;
use LightweightPlugins\Elallas\Woo\RefundLines;

/**
 * Renders the refund form, prefilled from RefundCalculator::defaults().
 */
final class CaseRefundSection {

	/**
	 * Render the section (nothing for cases that do not allow a refund).
	 *
	 * @param WithdrawalCase $case Case.
	 * @return void
	 */
	public static function render( WithdrawalCase $case ): void {
		if ( ! CaseStatus::allows_refund( $case->status ) ) {
			return;
		}

		echo '<h2 id="elallas-refund">' . esc_html__( 'Visszatérítés rögzítése a WooCommerce-ben', 'elallas-for-woo' ) . '</h2>';
		CaseRefundAction::render_notice();

		$order = OrderAdapter::get_order( $case->order_id );

		if ( null === $order ) {
			echo '<p>' . esc_html__( 'A rendelés nem tölthető be, ezért visszatérítés nem rögzíthető.', 'elallas-for-woo' ) . '</p>';
			return;
		}

		$lines    = RefundLines::for_case( $case, $order );
		$defaults = RefundCalculator::defaults( $lines, 'full' === $case->withdrawal_type );
		$decimals = wc_get_price_decimals();

		echo '<p class="description">' . esc_html__( 'A visszatérítés csak rögzítésre kerül a rendelésen (mint a WooCommerce „Kézi visszatérítés” gombja); a pénzt a bolt utalja vissza.', 'elallas-for-woo' ) . '</p>';
		self::invoicing_warning( $order );

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="elallas-refund-form" data-decimals="' . esc_attr( (string) $decimals ) . '">';
		wp_nonce_field( CaseRefundAction::ACTION );
		echo '<input type="hidden" name="action" value="' . esc_attr( CaseRefundAction::ACTION ) . '" />';
		echo '<input type="hidden" name="case_id" value="' . esc_attr( (string) $case->id ) . '" />';

		echo '<table class="widefat striped" style="max-width:900px;"><thead><tr><th>' . esc_html__( 'Tétel', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Visszatérítendő', 'elallas-for-woo' )
			. '</th><th>' . esc_html__( 'Készlet', 'elallas-for-woo' ) . '</th></tr></thead><tbody>';

		foreach ( $lines as $line ) {
			self::row( $line, $defaults[ (int) $line['item_id'] ], $decimals );
		}

		echo '</tbody><tfoot><tr><th>' . esc_html__( 'Összesen (bruttó)', 'elallas-for-woo' ) . '</th><th colspan="2"><strong class="elallas-refund-total">–</strong></th></tr></tfoot></table>';

		if ( CaseStatus::CLOSED !== $case->status ) {
			echo '<p><label><input type="checkbox" name="close_case" value="1" checked="checked" /> '
				. esc_html__( 'Ügy lezárása (a vásárló megkapja a szokásos státusz e-mailt)', 'elallas-for-woo' ) . '</label></p>';
		}

		submit_button( __( 'Visszatérítés rögzítése', 'elallas-for-woo' ), 'secondary', 'submit', false );
		echo '</form>';

		wp_enqueue_script( 'elallas-admin-refund', ELALLAS_FOR_WOO_URL . 'assets/js/admin-refund.js', [], ELALLAS_FOR_WOO_VERSION, true );
	}

	/**
	 * One product or cost row.
	 *
	 * @param array<string, mixed>     $line     Calculator line.
	 * @param array<string, int|float> $defaults Prefill and maximum.
	 * @param int                      $decimals Price decimals.
	 * @return void
	 */
	private static function row( array $line, array $defaults, int $decimals ): void {
		$id   = (int) $line['item_id'];
		$name = esc_html( (string) $line['name'] );

		if ( 'line_item' !== $line['type'] ) {
			$max = (float) $defaults['max'];
			printf(
				'<tr><td>%1$s</td><td><input type="number" name="items[%2$d][amount]" value="%3$s" min="0" max="%4$s" step="%5$s" class="small-text elallas-refund-amount" style="width:110px;" /> <span class="description">/ %6$s</span></td><td>–</td></tr>',
				$name, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				(int) $id,
				esc_attr( wc_format_decimal( (float) $defaults['amount'], $decimals ) ),
				esc_attr( wc_format_decimal( $max, $decimals ) ),
				esc_attr( $decimals > 0 ? '0.' . str_repeat( '0', $decimals - 1 ) . '1' : '1' ),
				wp_kses_post( wc_price( $max ) )
			);
			return;
		}

		$max = (int) $defaults['max'];

		if ( 0 === $max ) {
			echo '<tr><td>' . $name . '</td><td colspan="2">' . esc_html__( 'Nincs visszatérítendő mennyiség (már visszatérítve).', 'elallas-for-woo' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name is escaped above.
			return;
		}

		$unit    = ( (float) $line['total'] + array_sum( array_map( 'floatval', (array) $line['taxes'] ) ) ) / max( 1, (int) $line['qty_ordered'] );
		$restock = empty( $line['restockable'] )
			? esc_html__( 'nem kezelt készlet', 'elallas-for-woo' )
			: '<label><input type="checkbox" name="restock[]" value="' . (int) $id . '" checked="checked" /> ' . esc_html__( 'Visszakerül a készletbe', 'elallas-for-woo' ) . '</label>';

		printf(
			'<tr><td>%1$s%2$s</td><td><input type="number" name="items[%3$d][qty]" value="%4$d" min="0" max="%5$d" step="1" class="small-text elallas-refund-qty" data-unit="%6$s" /> <span class="description">/ %5$d %7$s</span></td><td>%8$s</td></tr>',
			$name, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			empty( $line['excepted'] ) ? '' : '<br /><span class="description">' . esc_html__( 'Kivételként jelölt termék', 'elallas-for-woo' ) . '</span>',
			(int) $id,
			(int) $defaults['qty'],
			(int) $max,
			esc_attr( (string) $unit ),
			esc_html__( 'db', 'elallas-for-woo' ),
			$restock // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
		);
	}

	/**
	 * Warn that an invoicing plugin may react to the WooCommerce refund.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	private static function invoicing_warning( \WC_Order $order ): void {
		if ( in_array( true, Invoicing::detect( $order ), true ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Számlázó bővítmény észlelve: a WooCommerce-visszatérítésre a saját beállításai szerint sztornó vagy helyesbítő számlát állíthat ki.', 'elallas-for-woo' ) . '</p></div>';
		}
	}
}
