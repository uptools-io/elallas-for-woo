<?php
/**
 * Onboarding wizard.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Onboarding;

use LightweightPlugins\Elallas\Admin\Settings\SettingsPage;
use LightweightPlugins\Elallas\Options;

/**
 * Five-step setup wizard for the withdrawal flow.
 */
final class Wizard {

	/**
	 * Render the wizard.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'elallas-for-woo' ) );
		}

		PageCreator::maybe_handle();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
		$step = max( 1, min( 5, $step ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Elállás beüzemelése', 'elallas-for-woo' ); ?></h1>
			<?php self::steps_nav( $step ); ?>
			<div class="card" style="max-width:760px;padding:8px 24px;">
				<?php self::step( $step ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Step navigation.
	 *
	 * @param int $current Current step.
	 * @return void
	 */
	private static function steps_nav( int $current ): void {
		$labels = self::labels();
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $labels as $num => $label ) {
			$url = add_query_arg(
				[
					'page' => 'elallas-for-woo-onboarding',
					'step' => $num,
				],
				admin_url( 'admin.php' )
			);
			printf(
				'<a href="%s" class="nav-tab %s">%d. %s</a>',
				esc_url( $url ),
				$num === $current ? 'nav-tab-active' : '',
				(int) $num,
				esc_html( $label )
			);
		}
		echo '</h2>';
	}

	/**
	 * Step labels.
	 *
	 * @return array<int, string>
	 */
	private static function labels(): array {
		return [
			1 => __( 'Bolt adatai', 'elallas-for-woo' ),
			2 => __( 'Elállási oldal', 'elallas-for-woo' ),
			3 => __( 'Megjelenítés', 'elallas-for-woo' ),
			4 => __( 'Határidő', 'elallas-for-woo' ),
			5 => __( 'Teszt', 'elallas-for-woo' ),
		];
	}

	/**
	 * Render a single step body.
	 *
	 * @param int $step Step number.
	 * @return void
	 */
	private static function step( int $step ): void {
		$settings = add_query_arg( [ 'page' => SettingsPage::SLUG ], admin_url( 'admin.php' ) );

		switch ( $step ) {
			case 1:
				echo '<p>' . esc_html__( 'Ellenőrizd a bolt nevét, címét és elérhetőségeit a WooCommerce beállításokban.', 'elallas-for-woo' ) . '</p>';
				self::link( admin_url( 'admin.php?page=wc-settings' ), __( 'WooCommerce beállítások', 'elallas-for-woo' ) );
				break;
			case 2:
				self::step_create_page();
				break;
			case 3:
				echo '<p>' . esc_html__( 'Állítsd be, hol jelenjen meg az elállási link (lábléc, fiók, rendelés).', 'elallas-for-woo' ) . '</p>';
				self::link( $settings . '&tab=general', __( 'Megjelenítés beállítása', 'elallas-for-woo' ) );
				break;
			case 4:
				echo '<p>' . esc_html__( 'Add meg az elállási határidőt és annak kezdő időpontját.', 'elallas-for-woo' ) . '</p>';
				self::link( $settings . '&tab=deadline', __( 'Határidő beállítása', 'elallas-for-woo' ) );
				break;
			case 5:
				echo '<p>' . esc_html__( 'Nyisd meg az elállási oldalt, és próbáld ki a folyamatot egy teszt rendeléssel.', 'elallas-for-woo' ) . '</p>';
				$page = (int) Options::get( 'withdrawal_page_id' );
				if ( $page > 0 ) {
					self::link( (string) get_permalink( $page ), __( 'Elállási oldal megnyitása', 'elallas-for-woo' ) );
				}
				break;
		}
	}

	/**
	 * Step 2 — create page form.
	 *
	 * @return void
	 */
	private static function step_create_page(): void {
		$page = (int) Options::get( 'withdrawal_page_id' );

		if ( $page > 0 ) {
			echo '<p>' . esc_html__( 'Az elállási oldal már létezik.', 'elallas-for-woo' ) . '</p>';
			self::link( get_edit_post_link( $page ) ?: '#', __( 'Oldal szerkesztése', 'elallas-for-woo' ) );
			return;
		}

		echo '<p>' . esc_html__( 'Létrehozunk egy "Elállás" oldalt az [elallas_form] rövidkóddal.', 'elallas-for-woo' ) . '</p>';
		echo '<form method="post" action="">';
		wp_nonce_field( PageCreator::NONCE );
		echo '<input type="hidden" name="elallas_create_page" value="1" />';
		submit_button( __( 'Elállási oldal létrehozása', 'elallas-for-woo' ) );
		echo '</form>';
	}

	/**
	 * Render a button link.
	 *
	 * @param string $url   URL.
	 * @param string $label Label.
	 * @return void
	 */
	private static function link( string $url, string $label ): void {
		printf( '<p><a class="button button-primary" href="%s">%s</a></p>', esc_url( $url ), esc_html( $label ) );
	}
}
