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
	 * Admin do_shortcode function.
	 *
	 * @param mixed $content The shortcode
	 * @param bool  $ignore_html Whether to ignore HTML tags.
	 * @return mixed The processed content.
	 */
	public static function admin_do_shortcode( $content, $ignore_html = false ) {
		global $shortcode_tags;

		if ( false === strpos( $content, '[' ) ) {
			return $content;
		}

		if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
			return $content;
		}

		// Find all registered tag names in $content.
		preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
		$tagnames = array_intersect( array_keys( $shortcode_tags ), $matches[1] );

		if ( empty( $tagnames ) ) {
			return $content;
		}

		$content = \do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames );

		$pattern = \get_shortcode_regex( $tagnames );
		$content = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $content );

		// Always restore square braces so we don't break things like <!--[if IE ]>.
		$content = \unescape_invalid_shortcodes( $content );

		return $content;
	}

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
