<?php
/**
 * WP class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'WP' ) ) {
	return;
}

/**
 * WP class
 */
abstract class WP {


	/**
	 * Admin do_shortcode function.
	 * 讓你可以在 wp-admin 後台也使用 do_shortcode
	 *
	 * @example: WP::admin_do_shortcode( '[your-shortcode]' );
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
	 * Sanitize Array
	 * Sanitize 一個深層的關聯陣列
	 *
	 * @param array $value Value to sanitize
	 * @return array|string
	 */
	public static function sanitize_text_field_deep( $value ) {
		if ( is_array( $value ) ) {
			// if array, sanitize each element
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::sanitize_text_field_deep( $item );
			}
			return $value;
		} else {
			// if not array, sanitize the value
			return \sanitize_text_field( $value );
		}
	}

	/**
	 * 檢查關聯陣列是否包含了必要參數
	 *
	 * @param array $params - 要檢查的參數 assoc array
	 * @param array $required_params - string[] 必要參數
	 * @param bool  $throw_in_rest - 是否在 REST API 中拋出錯誤
	 * @return \WP_REST_Response|bool
	 */
	public static function include_required_params( array $params, array $required_params, bool $throw_in_rest = false ) {

		$missing_params = array_diff( $required_params, array_keys( $params ) );
		if ( ! empty( $missing_params ) ) {
			if ( $throw_in_rest ) {
				return new \WP_REST_Response(
					array(
						'status'  => 500,
						'message' => 'missing required params',
						'data'    => \wp_json_encode( $missing_params ),
					),
					500
				);
			}
			return false;
		}
		return true;
	}


	/**
	 * 將關聯陣列顯示為 HTML
	 *
	 * @param array $arr - array
	 * @return string
	 */
	public static function array_to_html( array $arr ): string {

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
