<?php
/**
 * General class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils;

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
}
