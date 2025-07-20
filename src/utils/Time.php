<?php

declare( strict_types = 1 );

namespace J7\WpUtils;

/**
 * 時間工具
 */
abstract class Time {
	/**
	 * 將 local 時間字串轉換成 timestamp
	 *
	 * @param string $date_string 時間字串
	 * @return int|null
	 */
	public static function wp_strtotime( string $date_string ): int|null {
		$date_time = date_create($date_string, \wp_timezone());
		if (!$date_time) {
			return null;
		}
		return $date_time->getTimestamp();
	}
}
