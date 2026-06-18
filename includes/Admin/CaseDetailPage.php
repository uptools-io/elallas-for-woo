<?php
/**
 * Withdrawal case detail page.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Database\CaseRepository;

/**
 * Renders a single withdrawal case with all its sections.
 */
final class CaseDetailPage {

	/**
	 * Render the case detail screen.
	 *
	 * @param int $case_id Case ID.
	 * @return void
	 */
	public static function render( int $case_id ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod ehhez az oldalhoz.', 'elallas-for-woo' ) );
		}

		$case = CaseRepository::find( $case_id );

		if ( null === $case ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>'
				. esc_html__( 'Az ügy nem található.', 'elallas-for-woo' )
				. '</p></div></div>';
			return;
		}

		$back = add_query_arg( [ 'page' => 'elallas-for-woo' ], admin_url( 'admin.php' ) );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php
				printf(
					/* translators: %s: case number. */
					esc_html__( 'Elállási ügy: %s', 'elallas-for-woo' ),
					esc_html( $case->case_number )
				);
				?>
			</h1>
			<a href="<?php echo esc_url( $back ); ?>" class="page-title-action">
				<?php esc_html_e( 'Vissza a listához', 'elallas-for-woo' ); ?>
			</a>
			<hr class="wp-header-end" />

			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['updated'] ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>'
					. esc_html__( 'Az ügy státusza frissítve.', 'elallas-for-woo' )
					. '</p></div>';
			}

			CaseDetailSections::summary( $case );
			CaseDetailSections::items( $case );
			CaseDetailSections::audit( $case );
			CaseDetailSections::decision_form( $case );
			CaseDetailSections::documents( $case );
			?>
		</div>
		<?php
	}
}
