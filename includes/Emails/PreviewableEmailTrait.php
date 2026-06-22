<?php
/**
 * Shared email-preview support.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Emails;

use LightweightPlugins\Elallas\Models\CaseItem;
use LightweightPlugins\Elallas\Models\WithdrawalCase;
use LightweightPlugins\Elallas\Database\CaseItemRepository;

/**
 * Resolves the case + items for rendering.
 *
 * WooCommerce's in-admin email preview renders the content without calling
 * trigger(), so $this->object is not a real WithdrawalCase. In that case we
 * substitute sample data so the preview renders instead of throwing.
 */
trait PreviewableEmailTrait {

	/**
	 * Resolve the case and its items for the template.
	 *
	 * @return array{0: WithdrawalCase, 1: array<int, CaseItem>}
	 */
	private function resolve_case_items(): array {
		if ( $this->object instanceof WithdrawalCase ) {
			return [ $this->object, CaseItemRepository::for_case( $this->object->id ) ];
		}

		return [ WithdrawalCase::sample(), [ CaseItem::sample() ] ];
	}
}
