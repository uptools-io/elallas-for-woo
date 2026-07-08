<?php
/**
 * Status update email (plain text).
 *
 * @package LightweightPlugins\Elallas
 *
 * @var \LightweightPlugins\Elallas\Models\WithdrawalCase     $case
 * @var array<int, \LightweightPlugins\Elallas\Models\CaseItem> $items
 * @var string                                                $email_heading
 * @var bool                                                  $sent_to_admin
 * @var bool                                                  $plain_text
 * @var \WC_Email                                             $email
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

echo esc_html__( 'Tisztelt Vásárlónk!', 'elallas-for-woo' ) . "\n\n";

printf(
	/* translators: %s: case number */
	esc_html__( 'Tájékoztatjuk, hogy %s azonosítójú elállási ügyének állapota megváltozott.', 'elallas-for-woo' ),
	esc_html( $case->case_number )
);
echo "\n\n";

echo esc_html__( 'Ügyazonosító', 'elallas-for-woo' ) . ': ' . esc_html( $case->case_number ) . "\n";
echo esc_html__( 'Jelenlegi státusz', 'elallas-for-woo' ) . ': ' . esc_html( $case->status_label() ) . "\n\n";

$elallas_status_message = isset( $status_message ) ? trim( (string) $status_message ) : '';
if ( '' !== $elallas_status_message ) {
	echo esc_html__( 'A kereskedő üzenete', 'elallas-for-woo' ) . ":\n" . esc_html( wp_strip_all_tags( $elallas_status_message ) ) . "\n\n";
}

echo esc_html__( 'Ha kérdése van az elállási ügyével kapcsolatban, kérjük, válaszoljon erre az e-mailre, vagy vegye fel a kapcsolatot ügyfélszolgálatunkkal. A vételárat legkésőbb az elállás kézhezvételétől számított 14 napon belül visszatérítjük.', 'elallas-for-woo' ) . "\n\n";

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
