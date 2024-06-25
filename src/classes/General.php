<?php
/**
 * General class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'General' ) ) {
	return;
}
/**
 * General class
 */
abstract class General {

	/**
	 * JSON Parse
	 *
	 * @param string $json_string The string to parse.
	 * @param array  $default_value The default value.
	 * @return mixed The parsed value.
	 */
	public static function json_parse( $json_string, $default_value = array() ) {
		$output = '';
		try {
			$output = json_decode( str_replace( '\\', '', $json_string ) );
		} catch ( \Throwable $th ) {
			$output = $default_value;
		} finally {
			return $output;
		}
	}

	/**
	 * Spread Array
	 *
	 * @example array to html attribute string array_spread($args, '=', ' ')
	 * @example array to css styles string array_spread($args, ':', ';')
	 *
	 * @param array  $arr - array
	 * @param string $separator - separator
	 * @param string $end - end
	 * @return string
	 */
	public static function array_spread( array $arr, $separator = '=', $end = ' ' ): string {

		$spread = '';
		foreach ( $arr as $key => $value ) {
			$spread .= "{$key}{$separator}\"{$value}\"{$end}";
		}

		return $spread;
	}


	/**
	 * Array to html grid
	 * TODO
	 *
	 * @param array $arr - array
	 * @return string
	 */
	public static function array_to_grid( array $arr ): string {

		$style = '
		display: grid;
		gap: 1rem;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		';

		$html = '<div style="' . $style . '">';
		foreach ( $arr as $key => $value ) {
			if ( is_scalar( $value ) ) {
				if ( is_bool( $value ) ) {
					$value = $value ? 'true' : 'false';
				}
				$html .= "<div>{$key}:</div><div>{$value}</div>";
			} else {
				$html .= "<div>{$key}:</div><div>" . \wp_json_encode( $value ) . '</div>';
			}
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Generate a random string, using a cryptographically secure
	 * pseudorandom number generator (random_int)
	 *
	 * This function uses type hints now (PHP 7+ only), but it was originally
	 * written for PHP 5 as well.
	 *
	 * Credit: https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
	 *
	 * For PHP 7, random_int is a PHP core function
	 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
	 *
	 * @param int    $length      How many characters do we want?.
	 * @param string $keyspace base character set. [0-9a-zA-Z]
	 * @param string $extend A string of all possible characters to select from.
	 *
	 * @return string
	 * @throws \RangeException RangeException.
	 */
	public static function random_str( int $length = 64, ?string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ?string $extend = '' ): string {
		if ( $length < 1 ) {
			throw new \RangeException( 'Length must be a positive integer' );
		}
		$keyspace .= $extend; // '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';

		$pieces = array();
		$max    = mb_strlen( $keyspace, '8bit' ) - 1;
		for ( $i = 0; $i < $length; ++$i ) {
			$pieces [] = $keyspace[ random_int( 0, $max ) ];
		}
		return implode( '', $pieces );
	}

	/**
	 * 驗證字串是否為純英文字串 或者是 被 urlencode 過的字串
	 *
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function is_english( string $str ): bool {
		if ( self::contains_non_ascii( $str ) || self::is_urlencoded( $str ) ) {
			return false; // 包含非ASCII字符或已被 urlencode
		}

		return true; // 純英文字串
	}

	/**
	 * 檢查字串是否包含中文字元等非 ASCII 字元
	 *
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function contains_non_ascii( string $str ): bool {
		return preg_match( '/[^\x00-\x7F]/', $str ) !== 0;
	}

	/**
	 * 檢查字串是否已被 urlencode
	 *
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function is_urlencoded( string $str ): bool {
		$decoded = urldecode( $str );

		return $decoded !== $str;
	}
}
