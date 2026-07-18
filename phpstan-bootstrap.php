<?php
/**
 * PHPStan-only bootstrap.
 *
 * The main plugin file defines these constants at runtime with dynamic
 * values (`plugin_dir_path()` / `plugin_dir_url()`), which static analysis
 * cannot resolve — and bootstrapping the main file directly would execute
 * its autoloader requires and hook registrations. Declaring the constants
 * here (with representative string values) lets PHPStan type them without
 * running any plugin code. Loaded via `bootstrapFiles`.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

define( 'ELALLAS_FOR_WOO_VERSION', '1.0.13' );
define( 'ELALLAS_FOR_WOO_FILE', __DIR__ . '/elallas-for-woo.php' );
define( 'ELALLAS_FOR_WOO_PATH', __DIR__ . '/' );
define( 'ELALLAS_FOR_WOO_URL', 'https://example.test/wp-content/plugins/elallas-for-woo/' );
