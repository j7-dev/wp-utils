<?php
/**
 * Log class
 * 方便印出 Error Log
 *
 * @deprecated
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'Log' ) ) {
	return;
}

/**
 * Log class
 *
 * @deprecated 0.3.5
 */
abstract class Log extends ErrorLog {
}
