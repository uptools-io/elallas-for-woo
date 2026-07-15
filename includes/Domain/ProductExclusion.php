<?php
/**
 * Resolves whether a product is excluded from withdrawal.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

use LightweightPlugins\Elallas\Admin\ProductFields;
use LightweightPlugins\Elallas\Admin\TermFields;
use LightweightPlugins\Elallas\Integrations\Multilingual;

/**
 * Combines per-product exclusion (product meta) with taxonomy-based exclusion
 * (excluded product categories / tags, set as term meta on each term).
 */
final class ProductExclusion {

	/**
	 * Evaluate a product's exclusion status.
	 *
	 * @param int $product_id Product ID.
	 * @return array{0: bool, 1: string} [ excluded, reason label ].
	 */
	public static function evaluate( int $product_id ): array {
		// Read the exclusion meta off the default-language product, so a setting made
		// on the source product applies when a translated product is ordered.
		$canonical = Multilingual::default_object_id( $product_id, 'product' );

		if ( ProductFields::is_excluded( $canonical ) ) {
			$result = [ true, ProductFields::exclusion_label( $canonical ) ];
		} else {
			$result = self::taxonomy_exclusion( $canonical );
		}

		/**
		 * Filter the per-product withdrawal-exclusion decision.
		 *
		 * Lets integrations add dynamic exclusions (or clear one) beyond the
		 * product/category/tag meta — e.g. exclude by stock state or a custom rule.
		 *
		 * @since 1.0.13
		 * @param array{0: bool, 1: string} $result     [ excluded, reason label ].
		 * @param int                       $product_id Product ID.
		 */
		$result = (array) apply_filters( 'elallas_product_exclusion', $result, $product_id );

		return [ (bool) ( $result[0] ?? false ), (string) ( $result[1] ?? '' ) ];
	}

	/**
	 * Whether excluded items are BLOCKED from withdrawal (default), or merely
	 * FLAGGED for manual review (the pre-1.0.13 behaviour).
	 *
	 * @param \WC_Order|null $order Order in context, when available.
	 * @return bool
	 */
	public static function is_enforced( ?\WC_Order $order = null ): bool {
		/**
		 * Filter whether a withdrawal-excluded item blocks the withdrawal.
		 *
		 * Return false to restore the pre-1.0.13 behaviour, where an excluded
		 * item is only flagged (as `excepted`) for manual review and the customer
		 * can still submit it.
		 *
		 * @since 1.0.13
		 * @param bool           $enforced Whether exclusion blocks withdrawal.
		 * @param \WC_Order|null $order    Order in context (null when unavailable).
		 */
		return (bool) apply_filters( 'elallas_enforce_exclusion', true, $order );
	}

	/**
	 * Build a human-readable summary of excepted snapshot rows and their reasons.
	 *
	 * Used to explain a withdrawal refusal (rejection e-mail, front-end notice).
	 * Each line is "• <product name> — <reason>", or just the product name when no
	 * reason was recorded.
	 *
	 * @param array<int, array<string, mixed>> $excepted_rows Snapshot rows flagged excepted.
	 * @return string
	 */
	public static function summarize( array $excepted_rows ): string {
		$lines = [];

		foreach ( $excepted_rows as $row ) {
			$name   = trim( (string) ( $row['product_name_snapshot'] ?? '' ) );
			$reason = trim( (string) ( $row['eligibility_note'] ?? '' ) );

			if ( '' === $name ) {
				continue;
			}

			$lines[] = '' !== $reason
				? sprintf( '• %1$s — %2$s', $name, $reason )
				: sprintf( '• %s', $name );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Whether the product belongs to an excluded category or tag (term meta).
	 *
	 * @param int $product_id Product ID.
	 * @return array{0: bool, 1: string}
	 */
	private static function taxonomy_exclusion( int $product_id ): array {
		if ( $product_id <= 0 || ! function_exists( 'wp_get_post_terms' ) ) {
			return [ false, '' ];
		}

		foreach ( [ 'product_cat', 'product_tag' ] as $taxonomy ) {
			$term_ids = wp_get_post_terms( $product_id, $taxonomy, [ 'fields' => 'ids' ] );

			if ( ! is_array( $term_ids ) ) {
				continue;
			}

			foreach ( $term_ids as $term_id ) {
				// Canonicalize to the default-language term before reading its meta.
				$term_id = Multilingual::default_object_id( (int) $term_id, $taxonomy );

				if ( TermFields::is_excluded_term( (int) $term_id ) ) {
					return [ true, TermFields::term_reason_label( (int) $term_id ) ];
				}
			}
		}

		return [ false, '' ];
	}
}
