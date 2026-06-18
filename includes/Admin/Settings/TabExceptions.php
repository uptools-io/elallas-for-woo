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
 * Explains product-level withdrawal exceptions.
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
		<p>
			<?php
			esc_html_e(
				'A kivételeket termék szinten állíthatod be. Nyisd meg a kívánt terméket, és a "Termékadatok → Általános" fülön jelöld be az "Elállásból kizárt" lehetőséget, valamint válaszd ki a kizárás indokát.',
				'elallas-for-woo'
			);
			?>
		</p>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">
				<?php esc_html_e( 'Termékek megnyitása', 'elallas-for-woo' ); ?>
			</a>
		</p>
		<p class="description">
			<?php esc_html_e( 'Kategória szintű kizárás opcionális, terméken keresztül kezelhető.', 'elallas-for-woo' ); ?>
		</p>
		<?php
	}
}
