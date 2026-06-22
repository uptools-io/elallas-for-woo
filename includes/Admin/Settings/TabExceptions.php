<?php
/**
 * Exceptions Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Data\DefaultTexts;

/**
 * Explains where to set product-level and category/tag-level exceptions.
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
}
