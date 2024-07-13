<?php
/**
 * Singleton trait
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Traits;

if ( trait_exists( 'SingletonTrait' ) ) {
	return;
}

trait SingletonTrait {

	/**
	 * Singleton instance
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Get the singleton instance
	 *
	 * @param mixed ...$args Arguments
	 *
	 * @return self
	 */
	public static function instance(...$args) { // phpcs:ignore
		if ( null === self::$instance ) {
			self::$instance = new self(...$args);
		}

		return self::$instance;
	}
}
