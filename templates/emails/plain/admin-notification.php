<?php
/**
 * Admin notification email (plain text).
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

$elallas_order_url = admin_url( 'post.php?post=' . $case->order_id . '&action=edit' );
$elallas_case_url  = admin_url( 'admin.php?page=elallas-for-woo&view=case&case_id=' . $case->id );

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

echo esc_html__( 'Új elállási nyilatkozat érkezett az alábbi rendeléshez.', 'elallas-for-woo' ) . "\n\n";

echo esc_html__( 'Ügyazonosító', 'elallas-for-woo' ) . ': ' . esc_html( $case->case_number ) . "\n";
echo esc_html__( 'Rendelés', 'elallas-for-woo' ) . ': #' . esc_html( $case->order_number ) . "\n";
echo esc_html__( 'Rendelés szerkesztése', 'elallas-for-woo' ) . ': ' . esc_url_raw( $elallas_order_url ) . "\n";
echo esc_html__( 'Beérkezés időpontja', 'elallas-for-woo' ) . ': ' . esc_html( (string) $case->submitted_at ) . "\n";
echo esc_html__( 'Határidő státusza', 'elallas-for-woo' ) . ': ' . esc_html( $case->deadline_label() ) . "\n\n";

if ( ! empty( $items ) ) {
	echo esc_html__( 'Az elállással érintett termékek', 'elallas-for-woo' ) . ":\n";

	foreach ( $items as $elallas_item ) {
		echo '- ' . esc_html( $elallas_item->product_name_snapshot ) . ' x ' . esc_html( (string) $elallas_item->qty_withdrawn ) . "\n";
	}

	echo "\n";
}

echo esc_html__( 'Ügy megnyitása az adminban', 'elallas-for-woo' ) . ': ' . esc_url_raw( $elallas_case_url ) . "\n\n";

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
