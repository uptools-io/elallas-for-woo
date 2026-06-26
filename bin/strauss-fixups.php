<?php
/**
 * Post-Strauss fixups.
 *
 * Strauss rewrites namespace declarations, use statements and static class
 * references, but it does NOT rewrite namespaces embedded in *interpolated*
 * string literals. dompdf/php-font-lib builds class names dynamically, e.g.
 * `$class = "FontLib\\$class";` (Font.php) and
 * `$class = "FontLib\\$type\\TableDirectoryEntry";` (TrueType/File.php).
 *
 * After scoping, those still point at the original (now non-existent) global
 * `FontLib\…` classes and fatal with "Class FontLib\TrueType\File not found"
 * the moment dompdf has to parse a font file. This script prefixes those
 * remaining string literals with the configured Strauss namespace prefix.
 *
 * Run it after `php strauss.phar`.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

$root     = dirname( __DIR__ );
$composer = json_decode( (string) file_get_contents( $root . '/composer.json' ), true );
$prefix   = isset( $composer['extra']['strauss']['namespace_prefix'] ) ? (string) $composer['extra']['strauss']['namespace_prefix'] : '';

if ( '' === $prefix ) {
	fwrite( STDERR, "strauss-fixups: no extra.strauss.namespace_prefix in composer.json\n" );
	exit( 1 );
}

// json_decode gives single backslashes; the source uses escaped (double) backslashes.
$prefix_src = str_replace( '\\', '\\\\', rtrim( $prefix, '\\' ) . '\\' ); // e.g. LightweightPlugins\\Elallas\\Vendor\\

$dir = $root . '/vendor-prefixed/dompdf/php-font-lib/src';

if ( ! is_dir( $dir ) ) {
	fwrite( STDERR, "strauss-fixups: php-font-lib not found at $dir (run Strauss first)\n" );
	exit( 1 );
}

// Match the unprefixed FontLib namespace at the start of a string literal
// (single or double quoted), followed by an escaped backslash.
$patterns = array(
	'"FontLib\\\\' => '"' . $prefix_src . 'FontLib\\\\',
	"'FontLib\\\\" => "'" . $prefix_src . 'FontLib\\\\',
	// FontLib\TrueType\File::getFontType() reads the type segment by a fixed
	// index (`$class_parts[1]`), assuming "FontLib" is at index 0. Under scoping
	// the prefix shifts every segment, so read the type as the second-to-last
	// segment instead — correct whether scoped or not.
	'return $class_parts[1];' => 'return $class_parts[count($class_parts) - 2];',
);

$changed = 0;
$rii     = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );

foreach ( $rii as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}

	$contents = (string) file_get_contents( $file->getPathname() );
	$updated  = strtr( $contents, $patterns );

	if ( $updated !== $contents ) {
		file_put_contents( $file->getPathname(), $updated );
		fwrite( STDOUT, 'strauss-fixups: patched ' . substr( $file->getPathname(), strlen( $root ) + 1 ) . "\n" );
		++$changed;
	}
}

fwrite( STDOUT, "strauss-fixups: done ($changed file(s) patched)\n" );

// Verify no unprefixed FontLib references survive (fail the build loudly if the
// upstream package changes in a way these fixups no longer cover).
$leftover = array();
$rii      = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );

foreach ( $rii as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}

	$contents = (string) file_get_contents( $file->getPathname() );

	if ( str_contains( $contents, '"FontLib\\\\' ) || str_contains( $contents, "'FontLib\\\\" ) || str_contains( $contents, 'return $class_parts[1];' ) ) {
		$leftover[] = substr( $file->getPathname(), strlen( $root ) + 1 );
	}
}

if ( ! empty( $leftover ) ) {
	fwrite( STDERR, "strauss-fixups: FAILED — unprefixed FontLib reference(s) remain in:\n  " . implode( "\n  ", $leftover ) . "\n" );
	exit( 1 );
}

fwrite( STDOUT, "strauss-fixups: verified — no unprefixed FontLib references remain\n" );
