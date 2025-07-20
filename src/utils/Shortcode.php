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
}
