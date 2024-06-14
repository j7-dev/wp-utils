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
}
