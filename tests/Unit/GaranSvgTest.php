<?php
/**
 * GaranSvg tests against the real bundled official files.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\GaranData;
use LightweightPlugins\Elallas\Compliance\GaranSvg;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\GaranSvg
 */
final class GaranSvgTest extends TestCase {

	private static function source( string $variant ): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/assets/garan/garan-label-' . $variant . '.svg' );
	}

	private static function data( string $brand = 'Miele', string $model = 'WM 7000' ): GaranData {
		$data = GaranData::from_input( '12', $brand, $model, false );
		self::assertNotNull( $data );
		return $data;
	}

	private static function load( string $xml ): \DOMDocument {
		$doc = new \DOMDocument();
		self::assertTrue( $doc->loadXML( $xml ), 'output must be well-formed XML' );
		return $doc;
	}

	public function test_fill_colour_replaces_the_three_placeholders(): void {
		$out = GaranSvg::fill( self::source( 'colour' ), self::data(), 'elg1', false, 'Title', 'Desc' );
		$this->assertNotSame( '', $out );
		$this->assertStringContainsString( '>12<', $out );
		$this->assertStringContainsString( '>Miele<', $out );
		$this->assertStringContainsString( '>WM 7000<', $out );
		foreach ( [ '>XX<', 'Brand/', 'rademark', 'Model identifier' ] as $gone ) {
			$this->assertStringNotContainsString( $gone, $out );
		}
		$this->assertStringNotContainsString( '<?xml', $out );
	}

	public function test_fill_nested_replaces_only_years(): void {
		$out = GaranSvg::fill( self::source( 'nested' ), self::data(), 'elg2', true );
		$this->assertStringContainsString( '>12<', $out );
		$this->assertStringNotContainsString( '>XX<', $out );
		$this->assertStringNotContainsString( 'Miele', $out );
		$this->assertStringContainsString( 'aria-hidden="true"', $out );
	}

	/**
	 * @return array<string, array{0: string, 1: bool, 2: int}>
	 */
	public static function variants(): array {
		return [
			'colour' => [ 'colour', false, 697 ],
			'nested' => [ 'nested', true, 12 ],
		];
	}

	/**
	 * @dataProvider variants
	 */
	public function test_paths_are_unchanged( string $variant, bool $nested, int $paths ): void {
		$orig = self::load( self::source( $variant ) );
		$fill = self::load( GaranSvg::fill( self::source( $variant ), self::data(), 'elg3', $nested, 'T' ) );
		$d    = static function ( \DOMDocument $doc ): array {
			$out = [];
			foreach ( $doc->getElementsByTagName( 'path' ) as $p ) {
				$out[] = $p->getAttribute( 'd' );
			}
			sort( $out );
			return $out;
		};
		$this->assertCount( $paths, $d( $orig ) );
		$this->assertSame( $d( $orig ), $d( $fill ) );
	}

	/**
	 * DOM diff: after removing the prefix and the a11y additions, the filled
	 * tree equals the original except for the placeholder texts.
	 *
	 * @dataProvider variants
	 */
	public function test_dom_diff_only_texts_change( string $variant, bool $nested ): void {
		$prefix = 'elgdiff';
		$filled = GaranSvg::fill( self::source( $variant ), self::data(), $prefix, $nested, 'Title', 'Desc' );
		$filled = str_replace( [ $prefix . '-' ], '', $filled );

		$this->assertSame( self::canonical( self::source( $variant ) ), self::canonical( $filled ) );
	}

	private static function canonical( string $xml ): string {
		$doc  = self::load( $xml );
		$root = $doc->documentElement;
		self::assertInstanceOf( \DOMElement::class, $root );
		foreach ( [ 'role', 'aria-labelledby', 'aria-describedby', 'aria-hidden', 'focusable' ] as $attr ) {
			$root->removeAttribute( $attr );
		}
		foreach ( [ 'title', 'desc' ] as $tag ) {
			foreach ( iterator_to_array( $doc->getElementsByTagName( $tag ) ) as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}
		// Placeholder texts: drop their content (the only allowed difference).
		foreach ( $doc->getElementsByTagName( 'text' ) as $text ) {
			while ( $text->firstChild ) {
				$text->removeChild( $text->firstChild );
			}
		}
		return (string) $doc->C14N();
	}

	public function test_every_id_class_and_reference_is_prefixed(): void {
		$out = GaranSvg::fill( self::source( 'colour' ), self::data(), 'elgp', false, 'T', 'D' );
		$doc = self::load( $out );

		$ids = [];
		foreach ( ( new \DOMXPath( $doc ) )->query( '//*[@id]' ) as $el ) {
			$id = $el->getAttribute( 'id' );
			$this->assertStringStartsWith( 'elgp-', $id );
			$ids[ $id ] = true;
		}
		$this->assertGreaterThan( 10, count( $ids ) );

		foreach ( ( new \DOMXPath( $doc ) )->query( '//*[@class]' ) as $el ) {
			foreach ( preg_split( '/\s+/', trim( $el->getAttribute( 'class' ) ) ) as $token ) {
				$this->assertStringStartsWith( 'elgp-cls-', $token );
			}
		}

		$style = $doc->getElementsByTagName( 'style' )->item( 0 );
		$this->assertNotNull( $style );
		$this->assertDoesNotMatchRegularExpression( '/\.cls-\d/', $style->textContent );

		preg_match_all( '/url\(#([^)]+)\)/', $out, $refs );
		$this->assertNotEmpty( $refs[1] );
		foreach ( $refs[1] as $ref ) {
			$this->assertArrayHasKey( $ref, $ids, "url(#$ref) must resolve" );
		}
	}

	public function test_two_instances_share_no_id_or_class(): void {
		$a = GaranSvg::fill( self::source( 'colour' ), self::data(), 'elga', false, 'T' );
		$b = GaranSvg::fill( self::source( 'nested' ), self::data(), 'elgb', true );

		$tokens = static function ( string $svg ): array {
			preg_match_all( '/(?:id|class)="([^"]+)"/', $svg, $m );
			$out = [];
			foreach ( $m[1] as $v ) {
				foreach ( preg_split( '/\s+/', $v ) as $t ) {
					$out[] = $t;
				}
			}
			return array_unique( $out );
		};
		$this->assertSame( [], array_intersect( $tokens( $a ), $tokens( $b ) ) );
	}

	public function test_official_colours_are_kept(): void {
		$out = strtolower( GaranSvg::fill( self::source( 'colour' ), self::data(), 'elgc', false, 'T' ) );
		$this->assertStringContainsString( '#034ea2', $out );
		$this->assertStringContainsString( '#fff200', $out );
		$this->assertStringNotContainsString( '#003399', $out );
		$this->assertStringNotContainsString( '#ffed00', $out );
	}

	public function test_accessibility_attributes(): void {
		$doc  = self::load( GaranSvg::fill( self::source( 'colour' ), self::data(), 'elgt', false, 'EU GARAN', 'Desc' ) );
		$root = $doc->documentElement;
		$this->assertSame( 'img', $root->getAttribute( 'role' ) );
		$this->assertSame( 'elgt-title', $root->getAttribute( 'aria-labelledby' ) );
		$this->assertSame( 'false', $root->getAttribute( 'focusable' ) );
		$first = $root->firstChild;
		$this->assertInstanceOf( \DOMElement::class, $first );
		$this->assertSame( 'title', $first->localName );
		$this->assertSame( 'EU GARAN', $first->textContent );
	}

	public function test_values_are_xml_escaped(): void {
		$data = GaranData::from_input( '3', '<b>&"\'', 'A1', false );
		$this->assertNotNull( $data );
		$out = GaranSvg::fill( self::source( 'colour' ), $data, 'elge', false, 'T' );
		$this->assertStringNotContainsString( '<b>', $out );
		$this->assertStringContainsString( '&lt;b&gt;&amp;', $out );
		$doc = self::load( $out );
		$this->assertSame( '<b>&"\'', ( new \DOMXPath( $doc ) )->query( '//*[@data-elallas-field="brand"]' )->item( 0 )->textContent );
	}

	public function test_failures_return_empty_string(): void {
		$this->assertSame( '', GaranSvg::fill( '<svg><broken', self::data(), 'elg' ) );
		$this->assertSame( '', GaranSvg::fill( '', self::data(), 'elg' ) );
		$this->assertSame( '', GaranSvg::fill( '<svg xmlns="http://www.w3.org/2000/svg"><text>YY</text></svg>', self::data(), 'elg', true ) );
		// The full label needs all three placeholders.
		$this->assertSame( '', GaranSvg::fill( self::source( 'nested' ), self::data(), 'elg', false ) );
		// Invalid prefix.
		$this->assertSame( '', GaranSvg::fill( self::source( 'colour' ), self::data(), '1"bad' ) );
	}

	public function test_blank_empties_the_fields(): void {
		$out = GaranSvg::blank( self::source( 'colour' ) );
		$doc = self::load( $out );
		$fields = ( new \DOMXPath( $doc ) )->query( '//*[@data-elallas-field]' );
		$this->assertSame( 3, $fields->length );
		foreach ( $fields as $f ) {
			$this->assertSame( '', $f->textContent );
		}
		$this->assertStringContainsString( 'class="cls-3"', $out ); // No prefix.
	}
}
