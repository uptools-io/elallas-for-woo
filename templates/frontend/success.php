<?php
/**
 * Step 4: success / acknowledgement.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var \LightweightPlugins\Elallas\Models\WithdrawalCase|null $case Created case.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elallas_case_number = ( isset( $case ) && $case ) ? $case->case_number : '';
$elallas_submitted   = ( isset( $case ) && $case ) ? $case->submitted_at : '';
?>
<div class="elallas-success">
	<h2><?php esc_html_e( 'Elállási nyilatkozatát rögzítettük', 'elallas-for-woo' ); ?></h2>

	<p><?php esc_html_e( 'Köszönjük. Elállási nyilatkozatát rendszerünk rögzítette, és a visszaigazolást elküldtük a megadott e-mail címre (tartós adathordozó).', 'elallas-for-woo' ); ?></p>

	<ul class="elallas-order-summary">
		<li><strong><?php esc_html_e( 'Ügyazonosító:', 'elallas-for-woo' ); ?></strong> <?php echo esc_html( $elallas_case_number ); ?></li>
		<li><strong><?php esc_html_e( 'Beérkezés időpontja:', 'elallas-for-woo' ); ?></strong> <?php echo esc_html( $elallas_submitted ); ?></li>
	</ul>

	<p><?php esc_html_e( 'A nyilatkozat feldolgozásáról a kereskedő külön értesíti Önt.', 'elallas-for-woo' ); ?></p>
</div>
