<?php

declare( strict_types = 1 );

namespace J7\WpUtils;

/**
 * Shortcode 工具
 */
abstract class Shortcode {

	/**
	 * 判斷內容是否包含短碼
	 *
	 * @param string $content 內容
	 *
	 * @return bool
	 */
	public static function has_shortcode( string $content ): bool {
		return ( \str_contains( $content, '[' ) && \str_contains( $content, ']' ) );
	}

	/**
	 * Admin do_shortcode function.
	 * 讓你可以在 wp-admin 後台也使用 do_shortcode
	 *
	 * @example: WP::admin_do_shortcode( '[your-shortcode]' );
	 *
	 * @param string $content The shortcode
	 * @param ?bool  $ignore_html Whether to ignore HTML tags.
	 * @return string The processed content.
	 */
	public static function admin_do_shortcode( string $content, ?bool $ignore_html = false ) {
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

		/** @var bool $ignore_html */
		$content = \do_shortcodes_in_html_tags( $content, $ignore_html, $tagnames );

		$pattern = \get_shortcode_regex( $tagnames );
		$content = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $content ); // @phpstan-ignore-line

		// Always restore square braces so we don't break things like <!--[if IE ]>.
		$content = \unescape_invalid_shortcodes( (string) $content );

		return $content;
	}
}
