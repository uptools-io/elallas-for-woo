<?php
/**
 * Case response preparation trait.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Api;

use LightweightPlugins\Elallas\Models\CaseItem;
use LightweightPlugins\Elallas\Models\WithdrawalCase;

/**
 * Shared response formatting helpers for case endpoints.
 */
trait CaseResponseTrait {

	/**
	 * Prepare a case (with its items) for an admin response.
	 *
	 * @param WithdrawalCase       $case  Case object.
	 * @param array<int, CaseItem> $items Case items.
	 * @return array<string, mixed>
	 */
	private function prepare_case( WithdrawalCase $case, array $items ): array {
		return [
			'id'              => $case->id,
			'case_number'     => $case->case_number,
			'order_id'        => $case->order_id,
			'order_number'    => $case->order_number,
			'customer_id'     => $case->customer_id,
			'status'          => $case->status,
			'status_label'    => $case->status_label(),
			'withdrawal_type' => $case->withdrawal_type,
			'deadline_status' => $case->deadline_status,
			'deadline_label'  => $case->deadline_label(),
			'submitted_at'    => $case->submitted_at,
			'confirmed_at'    => $case->confirmed_at,
			'customer_note'   => $case->customer_note,
			'created_at'      => $case->created_at,
			'updated_at'      => $case->updated_at,
			'items'           => array_map( [ $this, 'prepare_item' ], $items ),
		];
	}

	/**
	 * Prepare a single case item for response.
	 *
	 * @param CaseItem $item Case item.
	 * @return array<string, mixed>
	 */
	private function prepare_item( CaseItem $item ): array {
		return [
			'id'               => $item->id,
			'order_item_id'    => $item->order_item_id,
			'product_id'       => $item->product_id,
			'variation_id'     => $item->variation_id,
			'name'             => $item->product_name_snapshot,
			'sku'              => $item->sku_snapshot,
			'qty_ordered'      => $item->qty_ordered,
			'qty_withdrawn'    => $item->qty_withdrawn,
			'line_total'       => $item->line_total_snapshot,
			'tax_total'        => $item->tax_total_snapshot,
			'eligibility_flag' => $item->eligibility_flag,
			'eligibility_note' => $item->eligibility_note,
		];
	}
}
