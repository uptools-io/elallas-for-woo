<?php
/**
 * Settings Page (tabbed).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Options;

/**
 * Renders the tabbed settings UI and handles saving.
 */
final class SettingsPage {

	/**
	 * Settings page slug.
	 */
	public const SLUG = 'elallas-for-woo-settings';

	/**
	 * Nonce action.
	 */
	private const NONCE = 'elallas_settings';

	/**
	 * Build the registered tab instances.
	 *
	 * @return array<int, TabInterface>
	 */
	private static function tabs(): array {
		return [
			new TabGeneral(),
			new TabDeadline(),
			new TabStatuses(),
			new TabExceptions(),
			new TabDocuments(),
			new TabPrivacy(),
			new TabEmails(),
			new TabLegal(),
		];
	}

	/**
	 * Render the settings page (static — called by AdminMenu).
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'elallas-for-woo' ) );
		}

		$tabs    = self::tabs();
		$saved   = self::maybe_save( $tabs );
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $tabs[0]->id(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Elállás – beállítások', 'elallas-for-woo' ); ?></h1>
			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Beállítások elmentve.', 'elallas-for-woo' ); ?></p></div>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab ) : ?>
					<a
						href="
						<?php
						echo esc_url(
							add_query_arg(
								[
									'page' => self::SLUG,
									'tab'  => $tab->id(),
								],
								admin_url( 'admin.php' )
							)
						);
						?>
								"
						class="nav-tab <?php echo $tab->id() === $current ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab->label() ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<form method="post" action="">
				<?php
				wp_nonce_field( self::NONCE );
				echo '<input type="hidden" name="elallas_tab" value="' . esc_attr( $current ) . '" />';

				foreach ( $tabs as $tab ) {
					if ( $tab->id() === $current ) {
						$tab->render();
					}
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the settings POST save.
	 *
	 * @param array<int, TabInterface> $tabs Registered tabs.
	 * @return bool Whether a save occurred.
	 */
	private static function maybe_save( array $tabs ): bool {
		if ( 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
			return false;
		}

		check_admin_referer( self::NONCE );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		$active = isset( $_POST['elallas_tab'] ) ? sanitize_key( wp_unslash( $_POST['elallas_tab'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each field is sanitized per-type by self::sanitize() in the loop below.
		$raw = isset( $_POST[ Options::OPTION_NAME ] ) ? (array) wp_unslash( $_POST[ Options::OPTION_NAME ] ) : [];

		foreach ( $tabs as $tab ) {
			if ( $tab->id() !== $active ) {
				continue;
			}

			foreach ( $tab->fields() as $key => $type ) {
				Options::set( $key, self::sanitize( $type, $raw[ $key ] ?? null ) );
			}
		}

		return true;
	}

	/**
	 * Sanitize a single field value by its declared type.
	 *
	 * @param string $type  Field type.
	 * @param mixed  $value Raw value.
	 * @return mixed
	 */
	private static function sanitize( string $type, mixed $value ): mixed {
		switch ( $type ) {
			case 'bool':
				return ! empty( $value );
			case 'int':
				return absint( $value );
			case 'key':
				return sanitize_key( (string) $value );
			case 'textarea':
				return sanitize_textarea_field( (string) $value );
			case 'array_key':
				return array_map( 'sanitize_key', (array) $value );
			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}
}
