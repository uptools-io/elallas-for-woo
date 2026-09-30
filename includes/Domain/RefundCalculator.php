<?php
/**
 * Refund amounts for a withdrawal case.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

/**
 * Pure refund math on plain arrays (no WooCommerce), shared by the refund
 * form (prefill) and the submit handler (authoritative recalculation).
 *
 * A line is either a product line — type "line_item" with qty_ordered,
 * qty_withdrawn, qty_refunded (whole order), qty_case_refunded (refunds
 * recorded for this case), total (net), taxes (rate id => amount) and
 * excepted — or a cost line — type "shipping"/"fee" with total, taxes,
 * total_refunded and tax_refunded. Product lines are refunded by quantity,
 * cost lines by a gross amount.
 */
final class RefundCalculator {

	/**
	 * Form prefill and maximum per line, keyed by order item ID.
	 *
	 * @param array<int, array<string, mixed>> $lines Lines.
	 * @param bool                             $full  Whether the case is a full withdrawal.
	 * @return array<int, array<string, int|float>> Product lines: qty, max. Cost lines: amount, max.
	 */
	public static function defaults( array $lines, bool $full ): array {
		$defaults = [];

		foreach ( $lines as $line ) {
			$id = (int) $line['item_id'];

			if ( self::is_product( $line ) ) {
				$max             = self::max_qty( $line );
				$defaults[ $id ] = [
					'qty' => empty( $line['excepted'] ) ? $max : 0,
					'max' => $max,
				];
				continue;
			}

			$max             = self::max_amount( $line );
			$defaults[ $id ] = [
				'amount' => $full ? $max : 0.0,
				'max'    => $max,
			];
		}

		return $defaults;
	}

	/**
	 * Build the wc_create_refund() line_items payload and total.
	 *
	 * @param array<int, array<string, mixed>> $lines    Lines.
	 * @param array<int, array<string, mixed>> $request  Item ID => [qty] or [amount] (gross).
	 * @param int                              $decimals Price decimals.
	 * @return array{line_items: array<int, array<string, mixed>>, amount: string}
	 */
	public static function build( array $lines, array $request, int $decimals ): array {
		$items = [];
		$sum   = 0.0;

		foreach ( $lines as $line ) {
			$id   = (int) $line['item_id'];
			$item = self::is_product( $line )
				? self::product_refund( $line, (int) ( $request[ $id ]['qty'] ?? 0 ), $decimals )
				: self::cost_refund( $line, (float) ( $request[ $id ]['amount'] ?? 0 ), $decimals );

			if ( null === $item ) {
				continue;
			}

			$items[ $id ] = $item;
			$sum         += (float) $item['refund_total'] + array_sum( array_map( 'floatval', $item['refund_tax'] ) );
		}

		return [
			'line_items' => $items,
			'amount'     => self::format( $sum, $decimals ),
		];
	}

	/**
	 * Refund row for a product line, or null when nothing is refunded.
	 *
	 * @param array<string, mixed> $line     Line.
	 * @param int                  $qty      Requested quantity.
	 * @param int                  $decimals Price decimals.
	 * @return array<string, mixed>|null
	 */
	private static function product_refund( array $line, int $qty, int $decimals ): ?array {
		$qty     = min( max( 0, $qty ), self::max_qty( $line ) );
		$ordered = (int) $line['qty_ordered'];

		if ( 0 === $qty || $ordered <= 0 ) {
			return null;
		}

		$ratio = $qty / $ordered;
		$taxes = [];

		foreach ( (array) $line['taxes'] as $rate => $tax ) {
			$taxes[ $rate ] = self::format( (float) $tax * $ratio, $decimals );
		}

		return [
			'qty'          => $qty,
			'refund_total' => self::format( (float) $line['total'] * $ratio, $decimals ),
			'refund_tax'   => $taxes,
		];
	}

	/**
	 * Refund row for a shipping/fee line from a gross amount, or null for 0.
	 *
	 * The gross amount is split by the line's own net/tax ratio; the net part
	 * takes the rounding remainder so the parts add up to the gross amount.
	 *
	 * @param array<string, mixed> $line     Line.
	 * @param float                $gross    Requested gross amount.
	 * @param int                  $decimals Price decimals.
	 * @return array<string, mixed>|null
	 */
	private static function cost_refund( array $line, float $gross, int $decimals ): ?array {
		$gross = round( min( max( 0.0, $gross ), self::max_amount( $line ) ), $decimals );
		$full  = self::gross( $line );

		if ( $gross <= 0.0 || $full <= 0.0 ) {
			return null;
		}

		$taxes    = [];
		$tax_part = 0.0;

		foreach ( (array) $line['taxes'] as $rate => $tax ) {
			$amount         = round( $gross * (float) $tax / $full, $decimals );
			$taxes[ $rate ] = self::format( $amount, $decimals );
			$tax_part      += $amount;
		}

		return [
			'qty'          => 0,
			'refund_total' => self::format( $gross - $tax_part, $decimals ),
			'refund_tax'   => $taxes,
		];
	}

	/**
	 * Refundable quantity left on a product line.
	 *
	 * @param array<string, mixed> $line Line.
	 * @return int
	 */
	private static function max_qty( array $line ): int {
		$order_left = (int) $line['qty_ordered'] - abs( (int) $line['qty_refunded'] );
		$case_left  = (int) $line['qty_withdrawn'] - abs( (int) ( $line['qty_case_refunded'] ?? 0 ) );

		return max( 0, min( $case_left, $order_left ) );
	}

	/**
	 * Refundable gross amount left on a cost line.
	 *
	 * @param array<string, mixed> $line Line.
	 * @return float
	 */
	private static function max_amount( array $line ): float {
		$refunded = abs( (float) $line['total_refunded'] ) + abs( (float) $line['tax_refunded'] );

		return max( 0.0, round( self::gross( $line ) - $refunded, 6 ) );
	}

	/**
	 * Gross (net + all taxes) of a line.
	 *
	 * @param array<string, mixed> $line Line.
	 * @return float
	 */
	private static function gross( array $line ): float {
		return (float) $line['total'] + array_sum( array_map( 'floatval', (array) $line['taxes'] ) );
	}

	/**
	 * Whether a line is refunded by quantity.
	 *
	 * @param array<string, mixed> $line Line.
	 * @return bool
	 */
	private static function is_product( array $line ): bool {
		return 'line_item' === ( $line['type'] ?? '' );
	}

	/**
	 * Decimal string for WooCommerce.
	 *
	 * @param float $value    Value.
	 * @param int   $decimals Decimals.
	 * @return string
	 */
	private static function format( float $value, int $decimals ): string {
		return number_format( round( $value, $decimals ), $decimals, '.', '' );
	}
}
