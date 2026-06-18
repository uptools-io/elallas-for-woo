<?php
/**
 * Withdrawal form block - server-side render.
 *
 * Delegates to the [elallas_form] shortcode so the block and the shortcode
 * render exactly the same multi-step front-end flow.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

echo do_shortcode( '[elallas_form]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
