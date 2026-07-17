<?php
/**
 * Exceptions Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Data\DefaultTexts;
use LightweightPlugins\Elallas\Admin\ProductFields;
use LightweightPlugins\Elallas\Admin\TermFields;

/**
 * Explains where to set product-level and category/tag-level exceptions, and
 * lists which products / categories / tags are currently excluded.
 */
final class TabExceptions implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'exceptions';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Kivételek', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Elállásból kizárt termékek', 'elallas-for-woo' ); ?></h2>
		<div class="notice notice-warning inline" style="margin:0 0 16px;">
			<p><?php echo esc_html( DefaultTexts::exception_warning() ); ?></p>
		</div>

		<?php $this->render_excluded_lists(); ?>

		<hr style="margin:24px 0;" />

		<h3><?php esc_html_e( 'Termék szinten', 'elallas-for-woo' ); ?></h3>
		<p>
			<?php
			esc_html_e(
				'Nyisd meg a kívánt terméket, és a "Termékadatok → Általános" fülön jelöld be az "Elállásból kizárt" lehetőséget, valamint válaszd ki a kizárás indokát.',
				'elallas-for-woo'
			);
			?>
		</p>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">
				<?php esc_html_e( 'Termékek megnyitása', 'elallas-for-woo' ); ?>
			</a>
		</p>

		<h3><?php esc_html_e( 'Kategória vagy címke szinten', 'elallas-for-woo' ); ?></h3>
		<p>
			<?php
			esc_html_e(
				'Nyiss meg egy termékkategóriát vagy -címkét szerkesztésre, és jelöld be az "Elállásból kizárt" lehetőséget az indokkal. Az adott kategóriába/címkébe tartozó termékek automatikusan "kizárt"-ként jelölődnek az elállási ügyekben. A termék szintű beállítás elsőbbséget élvez. A kizárás jelöl, nem automatikusan tilt.',
				'elallas-for-woo'
			);
			?>
		</p>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>">
				<?php esc_html_e( 'Kategóriák megnyitása', 'elallas-for-woo' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=product' ) ); ?>">
				<?php esc_html_e( 'Címkék megnyitása', 'elallas-for-woo' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render the lists of currently-excluded products, categories and tags.
	 *
	 * @return void
	 */
	private function render_excluded_lists(): void {
		$this->render_excluded_products();
		$this->render_excluded_terms();
	}

	/**
	 * List products excluded at the product level (capped at 100).
	 *
	 * @return void
	 */
	private function render_excluded_products(): void {
		$ids = get_posts(
			[
				'post_type'        => 'product',
				'post_status'      => [ 'publish', 'private', 'draft', 'pending' ],
				'posts_per_page'   => 100,
				'fields'           => 'ids',
				'meta_key'         => '_lw_elallas_excluded', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'orderby'          => 'title',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			]
		);

		echo '<h3>' . esc_html__( 'Kizárt termékek (termék szinten)', 'elallas-for-woo' ) . '</h3>';

		if ( empty( $ids ) ) {
			echo '<p class="description">' . esc_html__( 'Jelenleg egyetlen termék sincs termék szinten kizárva.', 'elallas-for-woo' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped" style="max-width:820px;"><thead><tr><th>'
			. esc_html__( 'Termék', 'elallas-for-woo' ) . '</th><th>'
			. esc_html__( 'Kizárás indoka', 'elallas-for-woo' ) . '</th><th></th></tr></thead><tbody>';

		foreach ( $ids as $product_id ) {
			$product_id = (int) $product_id;
			$reason     = ProductFields::exclusion_label( $product_id );
			$edit       = get_edit_post_link( $product_id );

			echo '<tr><td>' . esc_html( get_the_title( $product_id ) ) . '</td>';
			echo '<td>' . esc_html( '' !== $reason ? $reason : '—' ) . '</td><td>';
			if ( null !== $edit && '' !== $edit ) {
				echo '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Szerkesztés', 'elallas-for-woo' ) . '</a>';
			}
			echo '</td></tr>';
		}

		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Legfeljebb 100 termék jelenik meg.', 'elallas-for-woo' ) . '</p>';
	}

	/**
	 * List excluded product categories and tags.
	 *
	 * @return void
	 */
	private function render_excluded_terms(): void {
		$taxonomies = [
			'product_cat' => __( 'Kizárt kategóriák', 'elallas-for-woo' ),
			'product_tag' => __( 'Kizárt címkék', 'elallas-for-woo' ),
		];

		foreach ( $taxonomies as $taxonomy => $heading ) {
			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						[
							'key'   => '_lw_elallas_excluded',
							'value' => 'yes',
						],
					],
				]
			);

			echo '<h3>' . esc_html( $heading ) . '</h3>';

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				echo '<p class="description">' . esc_html__( 'Nincs kizárt elem.', 'elallas-for-woo' ) . '</p>';
				continue;
			}

			echo '<table class="widefat striped" style="max-width:820px;"><thead><tr><th>'
				. esc_html__( 'Név', 'elallas-for-woo' ) . '</th><th>'
				. esc_html__( 'Kizárás indoka', 'elallas-for-woo' ) . '</th><th></th></tr></thead><tbody>';

			foreach ( $terms as $term ) {
				$reason = TermFields::term_reason_label( (int) $term->term_id );
				$edit   = get_edit_term_link( (int) $term->term_id, $taxonomy );

				echo '<tr><td>' . esc_html( $term->name ) . '</td>';
				echo '<td>' . esc_html( '' !== $reason ? $reason : '—' ) . '</td><td>';
				if ( null !== $edit && '' !== $edit ) {
					echo '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Szerkesztés', 'elallas-for-woo' ) . '</a>';
				}
				echo '</td></tr>';
			}

			echo '</tbody></table>';
		}
	}
}
