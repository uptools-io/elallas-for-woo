<?php
/**
 * "Szavatosság és GARAN" settings tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Data\DefaultTexts;
use LightweightPlugins\Elallas\Options;

/**
 * Harmonised legal-guarantee notice and GARAN label settings.
 */
final class TabCompliance implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'compliance';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Szavatosság és GARAN', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [
			'notice_enabled'             => 'bool',
			'notice_label'               => 'text',
			'notice_display_product'     => 'bool',
			'notice_display_header'      => 'bool',
			'notice_display_footer'      => 'bool',
			'notice_display_checkout'    => 'bool',
			'notice_display_email'       => 'bool',
			'notice_email_mode'          => 'key',
			'notice_page_id'             => 'int',
			'garan_enabled'              => 'bool',
			'garan_product_mode'         => 'key',
			'garan_display_archive'      => 'bool',
			'garan_display_cart'         => 'bool',
			'garan_display_email'        => 'bool',
			'compliance_hide_b2b'        => 'bool',
			'compliance_exclude_virtual' => 'bool',
			'compliance_reviewed'        => 'bool',
			'product_info_enabled'       => 'bool',
		];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Szavatosság és GARAN', 'elallas-for-woo' ); ?></h2>
		<?php $this->render_created_message(); ?>
		<div class="notice notice-warning inline" style="margin:0 0 16px;">
			<p><strong><?php echo esc_html( DefaultTexts::disclaimer() ); ?></strong></p>
			<p><?php echo esc_html( DefaultTexts::compliance_intro() ); ?></p>
			<p><?php esc_html_e( 'Ismert korlátok ebben a verzióban:', 'elallas-for-woo' ); ?></p>
			<ul style="list-style:disc;margin-left:2em;">
				<li><?php esc_html_e( 'A GARAN címke az e-mailben csak akkor jelenik meg képként, ha a tárhelyen van GD FreeType-támogatás; enélkül szövegként (évek, gyártó, modell) és linkként.', 'elallas-for-woo' ); ?></li>
				<li><?php esc_html_e( 'A terméklistákon (archívum) megjelenő GARAN címke csak klasszikus témával működik; blokktémánál a termékoldalon, a kosárban és a pénztárban jelenik meg.', 'elallas-for-woo' ); ?></li>
				<li><?php esc_html_e( 'A szoftverfrissítési és javíthatósági információt 1.1.1-ig a termékleírásban tüntesd fel.', 'elallas-for-woo' ); ?></li>
				<li><?php esc_html_e( 'Gutenberg-blokk és Elementor-widget 1.1.1-től; addig a Shortcode blokkal / widgettel helyezd el a shortcode-okat.', 'elallas-for-woo' ); ?></li>
				<li><?php esc_html_e( 'A blokkos kosár expressz fizetési gombjai előtt a GARAN címke nem jelenik meg (a klasszikus kosárban igen).', 'elallas-for-woo' ); ?></li>
			</ul>
		</div>

		<h3><?php esc_html_e( 'Jogszabályi szavatosság tájékoztató', 'elallas-for-woo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Bekapcsolás', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'notice_enabled', __( 'Harmonizált szavatossági értesítés megjelenítése', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="notice_label"><?php esc_html_e( 'Felirat', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_text( 'notice_label', __( 'A felirat, amelyre kattintva/rámutatva a teljes hivatalos értesítés megjelenik. Üresen hagyva az alapértelmezett felirat jelenik meg.', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Megjelenítési helyek', 'elallas-for-woo' ); ?></th>
				<td>
					<?php $this->render_checkbox( 'notice_display_product', __( 'Termékoldal', 'elallas-for-woo' ) ); ?><br />
					<?php $this->render_checkbox( 'notice_display_header', __( 'Fejléc', 'elallas-for-woo' ) ); ?><br />
					<?php $this->render_checkbox( 'notice_display_footer', __( 'Lábléc', 'elallas-for-woo' ) ); ?><br />
					<?php $this->render_checkbox( 'notice_display_checkout', __( 'Pénztár (a rendelés gomb előtt: klasszikus, blokkos és „Rendelés kifizetése” oldal)', 'elallas-for-woo' ) ); ?><br />
					<?php $this->render_checkbox( 'notice_display_email', __( 'Rendelési e-mail', 'elallas-for-woo' ) ); ?>
					<p class="description"><?php esc_html_e( 'A fejléc csak a wp_body_open horgonyt támogató témákon jelenik meg. Egyéb helyre a [elallas_guarantee_notice] shortcode-dal (Shortcode blokk / Elementor Shortcode widget) teheted. A pénztári megjelenítés a rendelésnézetre (köszönőoldal, Fiókom) is vonatkozik.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="notice_email_mode"><?php esc_html_e( 'E-mail mód', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					self::render_limited_select(
						'notice_email_mode',
						[
							'image'      => __( 'Kép', 'elallas-for-woo' ),
							'attachment' => __( 'Színes PNG-melléklet', 'elallas-for-woo' ),
							'both'       => __( 'Mindkettő', 'elallas-for-woo' ),
						],
						[ 'image', 'attachment', 'both' ]
					);
					?>
					<p class="description"><?php esc_html_e( 'A vevői rendelési e-mailekben (feldolgozás alatt, teljesítve, várakozik, számla). Egyszerű szöveges e-mailhez a hivatalos színes PNG mindig mellékletként is csatolódik. Az angol nyelvű értesítéshez nincs hivatalos PNG, ott szöveg és link jelenik meg.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="notice_page_id"><?php esc_html_e( 'Szavatossági oldal', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_pages(
						[
							'name'              => esc_attr( Options::OPTION_NAME . '[notice_page_id]' ),
							'id'                => 'notice_page_id',
							'selected'          => (int) Options::get( 'notice_page_id' ),
							'show_option_none'  => esc_html__( '— Válassz oldalt —', 'elallas-for-woo' ),
							'option_none_value' => '0',
						]
					);
					?>
					<?php $this->render_create_page_link(); ?>
					<p class="description"><?php esc_html_e( 'Shortcode: [elallas_guarantee_notice] (kattintásra nyíló) vagy [elallas_guarantee_notice mode="inline"] (azonnal látható). Kiválasztott oldal esetén az értesítés alatt „Megnyitás külön oldalon” link jelenik meg.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
		</table>

		<?php ComplianceGaranSection::render(); ?>

		<h3><?php esc_html_e( 'Általános', 'elallas-for-woo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'B2B', 'elallas-for-woo' ); ?></th>
				<td>
					<?php $this->render_checkbox( 'compliance_hide_b2b', __( 'Elrejtés céges (B2B) rendeléseknél', 'elallas-for-woo' ) ); ?>
					<p class="description"><?php esc_html_e( 'Rendelésnézetben, a Rendelés kifizetése oldalon és e-mailben a számlázási cégnév/adószám alapján; termékoldalon, kosárban és pénztárban csak az elallas_compliance_is_b2b szűrővel.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Digitális termékek', 'elallas-for-woo' ); ?></th>
				<td>
					<?php $this->render_checkbox( 'compliance_exclude_virtual', __( 'Tisztán virtuális (digitális) termékeknél ne jelenjen meg', 'elallas-for-woo' ) ); ?>
					<p class="description"><?php esc_html_e( 'Virtuálisnak jelölt (nem szállítandó) termékeknél nem jelenik meg; ha virtuálisként kezelt fizikai árut árulsz, kapcsold ki ezt az opciót.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Termékinformációk', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'product_info_enabled', __( 'Opcionális termékinformációk (1.1.1-től)', 'elallas-for-woo' ) ); ?></td>
			</tr>
		</table>
		<p class="description"><?php esc_html_e( 'Többnyelvű bolt: az értesítés nyelve az oldal nyelvét követi (24 hivatalos nyelv). Polylang esetén a GARAN termékadatok szinkronizálását a Polylang (for WooCommerce) beállításaiban kapcsold be.', 'elallas-for-woo' ); ?></p>
		<input type="hidden" name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[compliance_reviewed]" value="1" />
		<?php
	}

	/**
	 * "Create page" link (admin-post, nonce) while no published notice page exists.
	 *
	 * @return void
	 */
	private function render_create_page_link(): void {
		$page_id = (int) Options::get( 'notice_page_id' );

		if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
			return;
		}

		$url = wp_nonce_url( admin_url( 'admin-post.php?action=elallas_create_notice_page' ), 'elallas_create_notice_page' );
		printf(
			' <a class="button" href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'Oldal létrehozása (/szavatossag/)', 'elallas-for-woo' )
		);
	}

	/**
	 * Result message of the "create page" action.
	 *
	 * @return void
	 */
	private function render_created_message(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag set by our own redirect.
		$created = isset( $_GET['created'] ) ? sanitize_key( wp_unslash( $_GET['created'] ) ) : '';

		if ( '1' === $created ) {
			$page_id = (int) Options::get( 'notice_page_id' );
			printf(
				'<div class="notice notice-success inline"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
				esc_html__( 'A szavatossági oldal elkészült.', 'elallas-for-woo' ),
				esc_url( (string) get_permalink( $page_id ) ),
				esc_html__( 'Megtekintés', 'elallas-for-woo' )
			);
		} elseif ( '0' === $created ) {
			printf( '<div class="notice notice-error inline"><p>%s</p></div>', esc_html__( 'Az oldal létrehozása nem sikerült.', 'elallas-for-woo' ) );
		}
	}

	/**
	 * Render a select whose not-yet-available choices are disabled.
	 *
	 * @param string                $name    Option key.
	 * @param array<string, string> $choices Value => label.
	 * @param array<int, string>    $enabled Selectable values.
	 * @return void
	 */
	public static function render_limited_select( string $name, array $choices, array $enabled ): void {
		$value = (string) Options::get( $name );
		printf( '<select id="%1$s" name="%2$s[%1$s]">', esc_attr( $name ), esc_attr( Options::OPTION_NAME ) );
		foreach ( $choices as $key => $label ) {
			printf(
				'<option value="%1$s" %2$s %3$s>%4$s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				disabled( ! in_array( $key, $enabled, true ), true, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}
}
