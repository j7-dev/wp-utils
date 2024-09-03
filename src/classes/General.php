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
	 * @param mixed  $default_value The default value.
	 * @return mixed The parsed value.
	 */
	public static function json_parse( $json_string, $default_value = [], $assoc = true ) {
		$output = '';
		try {
			$output = json_decode( str_replace( '\\', '', $json_string ), $assoc );
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

	/**
	 * Generate a random string, using a cryptographically secure
	 * pseudorandom number generator (random_int)
	 *
	 * This function uses type hints now (PHP 7+ only), but it was originally
	 * written for PHP 5 as well.
	 *
	 * Credit: https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
	 *
	 * For PHP 7, random_int is a PHP core function
	 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
	 *
	 * @param int    $length      How many characters do we want?.
	 * @param string $keyspace base character set. [0-9a-zA-Z]
	 * @param string $extend A string of all possible characters to select from.
	 *
	 * @return string
	 * @throws \RangeException RangeException.
	 */
	public static function random_str( int $length = 64, ?string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ?string $extend = '' ): string {
		if ( $length < 1 ) {
			throw new \RangeException( 'Length must be a positive integer' );
		}
		$keyspace .= $extend; // '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';

		$pieces = [];
		$max    = mb_strlen( $keyspace, '8bit' ) - 1;
		for ( $i = 0; $i < $length; ++$i ) {
			$pieces [] = $keyspace[ random_int( 0, $max ) ];
		}
		return implode( '', $pieces );
	}

	/**
	 * 驗證字串是否為純英文字串 或者是 被 urlencode 過的字串
	 *
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function is_english( string $str ): bool {
		if ( self::contains_non_ascii( $str ) || self::is_urlencoded( $str ) ) {
			return false; // 包含非ASCII字符或已被 urlencode
		}

		return true; // 純英文字串
	}

	/**
	 * 檢查字串是否包含中文字元等非 ASCII 字元
	 *
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function contains_non_ascii( string $str ): bool {
		return preg_match( '/[^\x00-\x7F]/', $str ) !== 0;
	}

	/**
	 * 檢查字串是否已被 urlencode
	 *
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function is_urlencoded( string $str ): bool {
		$decoded = urldecode( $str );

		return $decoded !== $str;
	}

	/**
	 * 比較兩個函數的效能
	 *
	 * @param array    $from_fn_array 原函數
	 * - callback callable function
	 * - args array 參數
	 * @param array    $to_fn_array 新函數
	 * - callback callable function
	 * - args array 參數
	 * @param int|null $sleep 2 個函數間隔時間，預設 3 秒
	 * @param int|null $precision 精確度，預設 5
	 *
	 * @return array
	 * - from array 原函數執行結果
	 * -- return mixed
	 * -- execution_time float
	 * - to array 新函數執行結果
	 * -- return mixed
	 * -- execution_time float
	 * - execution_time_diff float 執行時間差異.
	 * @throws \Exception
	 */
	public static function compare_performance(
		array $from_fn_array,
		array $to_fn_array,
		?int $sleep = 3,
		?int $precision = 5
	): array {
		$from_result = self::performance( $from_fn_array, $precision, false );
		sleep( $sleep );
		$to_result = self::performance( $to_fn_array, $precision, false );

		$execution_time_diff = $to_result['execution_time'] - $from_result['execution_time'];
		$percent             = abs( round( $execution_time_diff / $from_result['execution_time'], 2 ) ) * 100;

		\J7\WpUtils\Classes\Log::info(
			sprintf(
				'
		 效能比較
		 %1$s 花費時間: %2$s 豪秒
		 %3$s 花費時間: %4$s 豪秒
		 差異: %5$s 秒
		 %6$s %7$s%%',
				$from_result['function_name'],
				$from_result['execution_time'],
				$to_result['function_name'],
				$to_result['execution_time'],
				$execution_time_diff > 0 ? "+{$execution_time_diff}" : "{$execution_time_diff}",
				$execution_time_diff > 0 ? '❌ 變慢了' : '✅ 提升了',
				$percent
			)
		);

		return [
			'from'                => $from_result,
			'to'                  => $to_result,
			'execution_time_diff' => $execution_time_diff,
		];
	}

	/**
	 * 測試函數執行時間
	 *
	 * @param array     $fn_array
	 * - callback callable function
	 * - args array 參數
	 * @param int|null  $precision 精確度，預設 5
	 * @param bool|null $print_log 是否印出 log，預設 true
	 *
	 * @return array
	 * - return mixed
	 * - execution_time float
	 * @throws \Exception
	 */
	public static function performance( array $fn_array, ?int $precision = 5, ?bool $print_log = true ): array {
		$callback      = $fn_array['callback'] ?? '';
		$args          = $fn_array['args'] ?? [];
		$function_name = is_array( $callback ) ? "[{$callback[0]}, {$callback[1]}]" : $callback;

		if ( ! is_callable( $callback ) ) {
			throw new \Exception( "{$function_name} 不是可呼叫的函數" );
		}

		$start = microtime( true );

		$fn_return = call_user_func( $callback, ...$args );

		$end            = microtime( true );
		$execution_time = round( ( $end - $start ), $precision );

		if ( $print_log ) {
			\J7\WpUtils\Classes\Log::info(
				sprintf(
					'
		執行: %1$s
		花費時間: %2$s 秒',
					$function_name,
					$execution_time
				)
			);
		}

		return [
			'return'         => $fn_return,
			'execution_time' => $execution_time,
			'function_name'  => $function_name,
		];
	}

	/**
	 * 檢查單前網址是否在特定關鍵字內
	 *
	 * @param array $keywords 關鍵字
	 * @return bool
	 */
	public static function in_url( array $keywords ): bool {
	$request_uri = $_SERVER['REQUEST_URI'] ?? ''; // phpcs:ignore
		if (!$request_uri) {
			return false;
		}
		$in_url = false;

		foreach ($keywords as $keyword) {
			if (strpos($request_uri, $keyword) !== false) {
				$in_url = true;
				break;
			}
		}
		return $in_url;
	}

	/**
	 * 格式化數組，將 '[]' 轉為空數組。
	 *
	 * 遍歷數組，將值為 '[]' 的項目轉換為空數組。
	 *
	 * @param array $arr 原始數組。
	 * @return array 轉換後的數組。
	 */
	public static function format_empty_array( array $arr ): array {
		$formatted_array = [];
		foreach ($arr as $key => $value) {
			if ( '[]' === $value) {
				$formatted_array[ $key ] = [];
			} else {
				$formatted_array[ $key ] = $value;
			}
		}

		return $formatted_array;
	}
}
