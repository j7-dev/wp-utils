<?php
/**
 * Singleton trait
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils;

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
	 * @return self
	 */
	public static function instance() { // phpcs:ignore
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
