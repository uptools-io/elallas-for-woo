<?php
/**
 * RefundCalculator tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Domain\RefundCalculator;

/**
 * @covers \LightweightPlugins\Elallas\Domain\RefundCalculator
 */
final class RefundCalculatorTest extends TestCase {

	/**
	 * A product line: 3 × 1000 net, 27% VAT (rate 1).
	 *
	 * @param array<string, mixed> $overrides Field overrides.
	 * @return array<string, mixed>
	 */
	private function item( array $overrides = [] ): array {
		return array_merge(
			[
				'type'          => 'line_item',
				'item_id'       => 10,
				'qty_ordered'   => 3,
				'qty_withdrawn' => 3,
				'qty_refunded'  => 0,
				'total'         => '3000',
				'taxes'         => [ 1 => '810' ],
				'excepted'      => false,
			],
			$overrides
		);
	}

	/**
	 * A shipping line: 1000 net + 270 VAT.
	 *
	 * @param array<string, mixed> $overrides Field overrides.
	 * @return array<string, mixed>
	 */
	private function shipping( array $overrides = [] ): array {
		return array_merge(
			[
				'type'           => 'shipping',
				'item_id'        => 20,
				'total'          => '1000',
				'taxes'          => [ 1 => '270' ],
				'total_refunded' => '0',
				'tax_refunded'   => '0',
			],
			$overrides
		);
	}

	public function test_full_withdrawal_prefills_all_items_and_shipping(): void {
		$defaults = RefundCalculator::defaults( [ $this->item(), $this->shipping() ], true );

		$this->assertSame( 3, $defaults[10]['qty'] );
		$this->assertSame( 3, $defaults[10]['max'] );
		$this->assertSame( 1270.0, $defaults[20]['amount'] );
		$this->assertSame( 1270.0, $defaults[20]['max'] );
	}

	public function test_partial_withdrawal_prefills_withdrawn_qty_and_no_shipping(): void {
		$defaults = RefundCalculator::defaults( [ $this->item( [ 'qty_withdrawn' => 1 ] ), $this->shipping() ], false );

		$this->assertSame( 1, $defaults[10]['qty'] );
		$this->assertSame( 0.0, $defaults[20]['amount'] );
		$this->assertSame( 1270.0, $defaults[20]['max'] );
	}

	public function test_already_refunded_quantity_lowers_the_maximum(): void {
		$defaults = RefundCalculator::defaults( [ $this->item( [ 'qty_refunded' => 2 ] ) ], true );

		$this->assertSame( 1, $defaults[10]['max'] );
		$this->assertSame( 1, $defaults[10]['qty'] );
	}

	public function test_quantity_already_refunded_for_this_case_is_not_refunded_again(): void {
		// Withdrew 2 of 3; both were refunded for this case (the order still has 1 unrefunded).
		$line = $this->item(
			[
				'qty_withdrawn'     => 2,
				'qty_refunded'      => 2,
				'qty_case_refunded' => 2,
			]
		);

		$this->assertSame( 0, RefundCalculator::defaults( [ $line ], true )[10]['max'] );
		$this->assertSame( [], RefundCalculator::build( [ $line ], [ 10 => [ 'qty' => 2 ] ], 0 )['line_items'] );
	}

	public function test_excepted_item_defaults_to_zero_but_stays_refundable(): void {
		$defaults = RefundCalculator::defaults( [ $this->item( [ 'excepted' => true ] ) ], true );

		$this->assertSame( 0, $defaults[10]['qty'] );
		$this->assertSame( 3, $defaults[10]['max'] );
	}

	public function test_build_splits_item_net_and_tax_proportionally(): void {
		$result = RefundCalculator::build( [ $this->item() ], [ 10 => [ 'qty' => 2 ] ], 0 );

		$this->assertSame(
			[
				10 => [
					'qty'          => 2,
					'refund_total' => '2000',
					'refund_tax'   => [ 1 => '540' ],
				],
			],
			$result['line_items']
		);
		$this->assertSame( '2540', $result['amount'] );
	}

	public function test_build_clamps_requests_to_the_maximum(): void {
		$result = RefundCalculator::build(
			[ $this->item( [ 'qty_refunded' => 2 ] ), $this->shipping() ],
			[
				10 => [ 'qty' => 5 ],
				20 => [ 'amount' => '99999' ],
			],
			0
		);

		$this->assertSame( 1, $result['line_items'][10]['qty'] );
		$this->assertSame( '1000', $result['line_items'][20]['refund_total'] );
		$this->assertSame( '2540', $result['amount'] );
	}

	public function test_build_splits_gross_shipping_by_its_own_tax_ratio(): void {
		$result = RefundCalculator::build( [ $this->shipping() ], [ 20 => [ 'amount' => '635' ] ], 0 );

		$this->assertSame( [ 1 => '135' ], $result['line_items'][20]['refund_tax'] );
		$this->assertSame( '500', $result['line_items'][20]['refund_total'] );
		$this->assertSame( '635', $result['amount'] );
	}

	public function test_already_refunded_shipping_lowers_its_maximum(): void {
		$line     = $this->shipping(
			[
				'total_refunded' => '500',
				'tax_refunded'   => '135',
			]
		);
		$defaults = RefundCalculator::defaults( [ $line ], true );

		$this->assertSame( 635.0, $defaults[20]['max'] );
	}

	public function test_multiple_tax_rates_and_rounding_to_decimals(): void {
		$line   = $this->item(
			[
				'total' => '100.00',
				'taxes' => [
					1 => '27.00',
					2 => '5.00',
				],
			]
		);
		$result = RefundCalculator::build( [ $line ], [ 10 => [ 'qty' => 1 ] ], 2 );

		$this->assertSame( '33.33', $result['line_items'][10]['refund_total'] );
		$this->assertSame(
			[
				1 => '9.00',
				2 => '1.67',
			],
			$result['line_items'][10]['refund_tax']
		);
		$this->assertSame( '44.00', $result['amount'] );
	}

	public function test_zero_quantities_produce_no_lines_and_zero_amount(): void {
		$result = RefundCalculator::build( [ $this->item(), $this->shipping() ], [ 10 => [ 'qty' => 0 ] ], 0 );

		$this->assertSame( [], $result['line_items'] );
		$this->assertSame( '0', $result['amount'] );
	}
}
