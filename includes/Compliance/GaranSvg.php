<?php
/**
 * Fills the official GARAN SVG (pure).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM API property names.

/**
 * Replaces the placeholder texts of the official label with the merchant's
 * (validated) values and namespaces every id and `.cls-N` class so several
 * inline labels can share one HTML document.
 *
 * Nothing else changes: no colour, viewBox, path or style declaration is
 * touched. Values are inserted as DOM text nodes (XML-escaped). Never throws;
 * returns '' on any error (the caller logs).
 */
final class GaranSvg {

	private const NS = 'http://www.w3.org/2000/svg';

	/**
	 * Placeholder text => field key.
	 *
	 * @var array<string, string>
	 */
	private const PLACEHOLDERS = [
		'XX'               => 'years',
		'Brand/Trademark'  => 'brand',
		'Model identifier' => 'model',
	];

	/**
	 * Fill a label.
	 *
	 * @param string    $svg    Official SVG source.
	 * @param GaranData $data   Validated data.
	 * @param string    $prefix Instance prefix ([A-Za-z][A-Za-z0-9_-]*).
	 * @param bool      $nested Nested variant (only the years placeholder).
	 * @param string    $title  Accessible name ('' = decorative, aria-hidden).
	 * @param string    $desc   Accessible description.
	 * @return string SVG markup (no XML declaration) or '' on error.
	 */
	public static function fill( string $svg, GaranData $data, string $prefix, bool $nested = false, string $title = '', string $desc = '' ): string {
		if ( 1 !== preg_match( '/^[A-Za-z][A-Za-z0-9_-]*$/', $prefix ) ) {
			return '';
		}

		$values = [
			'years' => $data->years_label(),
			'brand' => $data->brand(),
			'model' => $data->model(),
		];

		return self::process( $svg, $values, $prefix, $nested, $title, $desc );
	}

	/**
	 * The label with empty editable fields (build-time raster base only).
	 *
	 * @param string $svg    Official SVG source.
	 * @param bool   $nested Nested variant.
	 * @return string
	 */
	public static function blank( string $svg, bool $nested = false ): string {
		return self::process(
			$svg,
			[
				'years' => '',
				'brand' => '',
				'model' => '',
			],
			'',
			$nested,
			'',
			''
		);
	}

	/**
	 * Shared DOM pipeline.
	 *
	 * @param string                $svg    Source.
	 * @param array<string, string> $values Field values.
	 * @param string                $prefix Prefix ('' = none).
	 * @param bool                  $nested Nested variant.
	 * @param string                $title  Accessible name.
	 * @param string                $desc   Description.
	 * @return string
	 */
	private static function process( string $svg, array $values, string $prefix, bool $nested, string $title, string $desc ): string {
		$previous = libxml_use_internal_errors( true );
		$doc      = new \DOMDocument();
		$loaded   = '' !== $svg && $doc->loadXML( $svg, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded || ! $doc->documentElement instanceof \DOMElement || 'svg' !== $doc->documentElement->localName ) {
			return '';
		}

		$xpath = new \DOMXPath( $doc );
		$xpath->registerNamespace( 'svg', self::NS );

		if ( ! self::replace_texts( $doc, $xpath, $values, $nested ) ) {
			return '';
		}

		$comments = $xpath->query( '//comment()' );
		foreach ( false === $comments ? [] : iterator_to_array( $comments ) as $comment ) {
			if ( $comment->parentNode ) {
				$comment->parentNode->removeChild( $comment );
			}
		}

		if ( '' !== $prefix ) {
			self::prefix_document( $doc, $xpath, $prefix );
			self::add_a11y( $doc, $doc->documentElement, $prefix, $title, $desc );
		}

		$out = $doc->saveXML( $doc->documentElement );

		return false === $out ? '' : $out;
	}

	/**
	 * Replace every placeholder <text> with one tspan holding the value.
	 *
	 * @param \DOMDocument          $doc    Document.
	 * @param \DOMXPath             $xpath  XPath.
	 * @param array<string, string> $values Field values.
	 * @param bool                  $nested Nested variant.
	 * @return bool False when the placeholders are not exactly as expected.
	 */
	private static function replace_texts( \DOMDocument $doc, \DOMXPath $xpath, array $values, bool $nested ): bool {
		$targets = [];
		foreach ( self::PLACEHOLDERS as $placeholder => $field ) {
			$nodes    = $xpath->query( '//svg:text[normalize-space(.)="' . $placeholder . '"]' );
			$count    = $nodes ? $nodes->length : 0;
			$expected = ( $nested && 'years' !== $field ) ? 0 : 1;
			if ( $count !== $expected ) {
				return false;
			}
			if ( 1 === $count && $nodes && $nodes->item( 0 ) instanceof \DOMElement ) {
				$targets[ $field ] = $nodes->item( 0 );
			}
		}

		foreach ( $targets as $field => $text ) {
			while ( $text->firstChild ) {
				$text->removeChild( $text->firstChild );
			}
			$tspan = $doc->createElementNS( self::NS, 'tspan' );
			$tspan->setAttribute( 'x', '0' );
			$tspan->setAttribute( 'y', '0' );
			$tspan->setAttribute( 'data-elallas-field', $field );
			$tspan->appendChild( $doc->createTextNode( $values[ $field ] ?? '' ) );
			$text->appendChild( $tspan );
		}

		return true;
	}

	/**
	 * Prefix ids, id references and `cls-N` classes (attributes and <style>).
	 *
	 * @param \DOMDocument $doc    Document.
	 * @param \DOMXPath    $xpath  XPath.
	 * @param string       $prefix Prefix.
	 * @return void
	 */
	private static function prefix_document( \DOMDocument $doc, \DOMXPath $xpath, string $prefix ): void {
		$elements = $xpath->query( '//*' );
		foreach ( false === $elements ? [] : $elements as $el ) {
			if ( ! $el instanceof \DOMElement ) {
				continue;
			}
			if ( $el->hasAttribute( 'id' ) ) {
				$el->setAttribute( 'id', $prefix . '-' . $el->getAttribute( 'id' ) );
			}
			if ( $el->hasAttribute( 'class' ) ) {
				$el->setAttribute( 'class', self::prefix_classes( $el->getAttribute( 'class' ), $prefix ) );
			}
			foreach ( iterator_to_array( $el->attributes ) as $attr ) {
				if ( $attr instanceof \DOMAttr && in_array( $attr->localName, [ 'id', 'class' ], true ) ) {
					continue;
				}
				if ( $attr instanceof \DOMAttr && false !== strpos( $attr->value, '#' ) ) {
					$attr->value = self::prefix_refs( $attr->value, $prefix, 'href' === $attr->localName );
				}
			}
			if ( 'style' === $el->localName ) {
				$css = (string) preg_replace( '/\.cls-(\d+)\b/', '.' . $prefix . '-cls-$1', $el->textContent );
				$css = self::prefix_refs( $css, $prefix, false );
				while ( $el->firstChild ) {
					$el->removeChild( $el->firstChild );
				}
				$el->appendChild( $doc->createTextNode( $css ) );
			}
		}
	}

	/**
	 * Prefix `cls-N` tokens of a class attribute.
	 *
	 * @param string $classes Class attribute.
	 * @param string $prefix  Prefix.
	 * @return string
	 */
	private static function prefix_classes( string $classes, string $prefix ): string {
		return (string) preg_replace( '/(?<![\w-])cls-(\d+)(?![\w-])/', $prefix . '-cls-$1', $classes );
	}

	/**
	 * Prefix `url(#id)` references and, for href attributes, `#id`.
	 *
	 * @param string $value   Attribute or CSS text.
	 * @param string $prefix  Prefix.
	 * @param bool   $is_href Attribute is (xlink:)href.
	 * @return string
	 */
	private static function prefix_refs( string $value, string $prefix, bool $is_href ): string {
		$value = (string) preg_replace( '/url\(\s*([\'"]?)#/', 'url($1#' . $prefix . '-', $value );
		if ( $is_href && 0 === strpos( $value, '#' ) ) {
			$value = '#' . $prefix . '-' . substr( $value, 1 );
		}

		return $value;
	}

	/**
	 * Root accessibility: role/title/desc, or aria-hidden when decorative.
	 *
	 * @param \DOMDocument $doc    Document.
	 * @param \DOMElement  $root   <svg> element.
	 * @param string       $prefix Prefix.
	 * @param string       $title  Accessible name.
	 * @param string       $desc   Description.
	 * @return void
	 */
	private static function add_a11y( \DOMDocument $doc, \DOMElement $root, string $prefix, string $title, string $desc ): void {
		$root->setAttribute( 'focusable', 'false' );

		if ( '' === $title ) {
			$root->setAttribute( 'aria-hidden', 'true' );
			return;
		}

		$root->setAttribute( 'role', 'img' );
		$root->setAttribute( 'aria-labelledby', $prefix . '-title' );

		$first = $root->firstChild;
		foreach ( [
			'title' => $title,
			'desc'  => $desc,
		] as $tag => $text ) {
			if ( '' === $text ) {
				continue;
			}
			$node = $doc->createElementNS( self::NS, $tag );
			$node->setAttribute( 'id', $prefix . '-' . $tag );
			$node->appendChild( $doc->createTextNode( $text ) );
			$root->insertBefore( $node, $first );
		}
		if ( '' !== $desc ) {
			$root->setAttribute( 'aria-describedby', $prefix . '-desc' );
		}
	}
}
