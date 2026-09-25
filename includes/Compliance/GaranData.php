<?php
/**
 * GARAN label data value object (pure).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * Immutable, validated GARAN data: years, brand, model.
 *
 * Only a value that passes every rule (duration > 2 years, fits the fixed
 * slots of both label variants, non-empty brand and model without control
 * characters) can be constructed.
 */
final class GaranData {

	/**
	 * Per-field error codes returned by errors().
	 */
	public const ERR_EMPTY     = 'empty';
	public const ERR_INVALID   = 'invalid';
	public const ERR_TOO_WIDE  = 'too_wide';
	public const ERR_CONTROL   = 'control';
	private const MIN_YEARS    = 3;
	private const MAX_YEARS    = 99;
	private const MAX_HALF     = 9.5;
	private const CONTROL_CHAR = '/[\x00-\x1F\x7F]/u';

	/**
	 * Canonical years ('3', '2.5').
	 *
	 * @var string
	 */
	private string $years;

	/**
	 * Brand / trademark.
	 *
	 * @var string
	 */
	private string $brand;

	/**
	 * Model identifier.
	 *
	 * @var string
	 */
	private string $model;

	/**
	 * Constructor (use the factories).
	 *
	 * @param string $years Canonical years.
	 * @param string $brand Brand.
	 * @param string $model Model.
	 */
	private function __construct( string $years, string $brand, string $model ) {
		$this->years = $years;
		$this->brand = $brand;
		$this->model = $model;
	}

	/**
	 * Canonicalise a duration: '3' / ' 5 ' → '3' / '5', '2,5' → '2.5' (halves
	 * only when allowed, 2.5–9.5). Whole years 3–99 that fit both labels.
	 *
	 * @param string $raw        Raw input (comma or dot decimal).
	 * @param bool   $allow_half Accept x.5 values.
	 * @return string|null Canonical value or null when invalid.
	 */
	public static function normalize_years( string $raw, bool $allow_half ): ?string {
		$raw = str_replace( ',', '.', trim( $raw ) );
		if ( 1 !== preg_match( '/^\d{1,2}(\.\d{1,3})?$/', $raw ) ) {
			return null;
		}

		$value = (float) $raw;
		if ( $value <= 2 || 0.0 !== fmod( $value * 2, 1.0 ) ) {
			return null;
		}

		$whole = 0.0 === fmod( $value, 1.0 );
		if ( $whole ) {
			if ( $value < self::MIN_YEARS || $value > self::MAX_YEARS ) {
				return null;
			}
			$canonical = (string) (int) $value;
		} else {
			if ( ! $allow_half || $value > self::MAX_HALF ) {
				return null;
			}
			$canonical = (int) floor( $value ) . '.5';
		}

		$label = str_replace( '.', ',', $canonical );
		if ( ! GaranMetrics::fits_years( $label ) || ! GaranMetrics::fits_years( $label, true ) ) {
			return null;
		}

		return $canonical;
	}

	/**
	 * Validate the three fields.
	 *
	 * @param string $years      Raw years.
	 * @param string $brand      Brand.
	 * @param string $model      Model.
	 * @param bool   $allow_half Accept x.5 years.
	 * @return array<string, string> field (years|brand|model) => error code; empty when valid.
	 */
	public static function errors( string $years, string $brand, string $model, bool $allow_half ): array {
		$errors = [];

		if ( '' === trim( $years ) ) {
			$errors['years'] = self::ERR_EMPTY;
		} elseif ( null === self::normalize_years( $years, $allow_half ) ) {
			$errors['years'] = self::ERR_INVALID;
		}

		foreach ( [
			'brand' => $brand,
			'model' => $model,
		] as $field => $value ) {
			$error = self::text_error( $value, 'brand' === $field );
			if ( '' !== $error ) {
				$errors[ $field ] = $error;
			}
		}

		return $errors;
	}

	/**
	 * Build from (already sanitised) input.
	 *
	 * @param string $years      Raw years.
	 * @param string $brand      Brand.
	 * @param string $model      Model.
	 * @param bool   $allow_half Accept x.5 years.
	 * @return self|null
	 */
	public static function from_input( string $years, string $brand, string $model, bool $allow_half ): ?self {
		if ( [] !== self::errors( $years, $brand, $model, $allow_half ) ) {
			return null;
		}

		return new self( (string) self::normalize_years( $years, $allow_half ), trim( $brand ), trim( $model ) );
	}

	/**
	 * Rebuild from a stored snapshot.
	 *
	 * Half years stored at purchase time stay valid even if halves were
	 * disabled since (the purchase-time data is authoritative); widths are
	 * still checked.
	 *
	 * @param array<string, mixed> $data Snapshot.
	 * @return self|null
	 */
	public static function from_array( array $data ): ?self {
		$fields = [];
		foreach ( [ 'years', 'brand', 'model' ] as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_scalar( $data[ $key ] ) ) {
				return null;
			}
			$fields[ $key ] = (string) $data[ $key ];
		}

		return self::from_input( $fields['years'], $fields['brand'], $fields['model'], true );
	}

	/**
	 * Snapshot array.
	 *
	 * @return array{years: string, brand: string, model: string}
	 */
	public function to_array(): array {
		return [
			'years' => $this->years,
			'brand' => $this->brand,
			'model' => $this->model,
		];
	}

	/**
	 * Canonical years ('3', '2.5').
	 *
	 * @return string
	 */
	public function years(): string {
		return $this->years;
	}

	/**
	 * Years as printed on the label (decimal comma: '2,5').
	 *
	 * @return string
	 */
	public function years_label(): string {
		return str_replace( '.', ',', $this->years );
	}

	/**
	 * Whether the duration is a half year.
	 *
	 * @return bool
	 */
	public function is_half_year(): bool {
		return false !== strpos( $this->years, '.' );
	}

	/**
	 * Brand.
	 *
	 * @return string
	 */
	public function brand(): string {
		return $this->brand;
	}

	/**
	 * Model identifier.
	 *
	 * @return string
	 */
	public function model(): string {
		return $this->model;
	}

	/**
	 * Stable hash of the data and the official asset version.
	 *
	 * @param string $asset_version OfficialAssets::ASSET_VERSION.
	 * @return string
	 */
	public function hash( string $asset_version ): string {
		return sha1( $this->years . '|' . $this->brand . '|' . $this->model . '|' . $asset_version );
	}

	/**
	 * Error code of a brand/model value ('' when valid).
	 *
	 * @param string $value    Value.
	 * @param bool   $is_brand Brand (else model).
	 * @return string
	 */
	private static function text_error( string $value, bool $is_brand ): string {
		if ( 1 !== preg_match( '//u', $value ) || 1 === preg_match( self::CONTROL_CHAR, $value ) ) {
			return self::ERR_CONTROL;
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return self::ERR_EMPTY;
		}

		$fits = $is_brand ? GaranMetrics::fits_brand( $value ) : GaranMetrics::fits_model( $value );

		return $fits ? '' : self::ERR_TOO_WIDE;
	}
}
