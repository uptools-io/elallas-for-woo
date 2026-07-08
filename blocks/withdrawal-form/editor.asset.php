<?php
/**
 * Asset manifest for the withdrawal-form block editor script.
 *
 * Hand-authored (the block ships a plain, unbuilt editor.js). Declaring the
 * dependencies here makes WordPress load `wp-i18n` before editor.js, which is
 * what enables automatic JavaScript translation via wp_set_script_translations().
 *
 * @package LightweightPlugins\Elallas
 */

return [
	'dependencies' => [ 'wp-element', 'wp-blocks', 'wp-block-editor', 'wp-i18n' ],
	'version'      => defined( 'ELALLAS_FOR_WOO_VERSION' ) ? ELALLAS_FOR_WOO_VERSION : '1.0.11',
];
