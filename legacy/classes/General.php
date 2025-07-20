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
	 * @deprecated 不需要這個
	 *
	 * @param string $json_string The string to parse.
	 * @param mixed  $default_value The default value.
	 * @param bool   $assoc 是否轉為關聯數組，預設 true
	 * @return mixed The parsed value.
	 */
	public static function json_parse( $json_string, $default_value = [], $assoc = true ) {
		$output = '';
		try {
			$output = json_decode( $json_string, $assoc, 512, JSON_UNESCAPED_UNICODE );
		} catch ( \Throwable $th ) {
			$output = $default_value;
		} finally {
			return $output;
		}
	}

	/**
	 * Spread Array
	 *
	 * @deprecated 用 array_reduce 就好不需要這個
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
	 *
	 * @deprecated \J7\WpHelpers\Arr::create($arr)->to_html()
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
	 * @deprecated \J7\WpAbstracts\Str::random()
	 *
	 * @param int    $length      How many characters do we want?.
	 * @param string $keyspace base character set. [0-9a-zA-Z]
	 * @param string $extend A string of all possible characters to select from.
	 *
	 * @return string
	 * @throws \RangeException RangeException.
	 */
	public static function random_str( int $length = 64, ?string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ?string $extend = '' ): string {
		return \J7\WpHelpers\Str::random( $length, $keyspace, $extend );
	}

	/**
	 * 驗證字串是否為純英文字串 或者是 被 urlencode 過的字串
	 *
	 * @deprecated \J7\WpHelpers\Str::is_english()
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function is_english( string $str ): bool {
		return ( new \J7\WpHelpers\Str( $str ) )->is_english();
	}

	/**
	 * 檢查字串是否包含中文字元等非 ASCII 字元
	 *
	 * @deprecated \J7\WpHelpers\Str::contains_non_ascii()
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function contains_non_ascii( string $str ): bool {
		return ( new \J7\WpHelpers\Str( $str ) )->contains_non_ascii();
	}

	/**
	 * 檢查字串是否已被 urlencode
	 *
	 * @param string $str - 字串
	 *
	 * @return bool
	 */
	public static function is_urlencoded( string $str ): bool {
		return ( new \J7\WpHelpers\Str( $str ) )->is_urlencoded();
	}

	/**
	 * 比較兩個函數的效能
	 *
	 * @deprecated \J7\WpUtils\Performance::compare_performance()
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
	 * @throws \Exception 不是可呼叫的函數
	 */
	public static function compare_performance(
		array $from_fn_array,
		array $to_fn_array,
		?int $sleep = 3,
		?int $precision = 5
	): array {
		return \J7\WpUtils\Performance::compare_performance( $from_fn_array, $to_fn_array, $sleep, $precision );
	}

	/**
	 * 測試函數執行時間
	 *
	 * @deprecated \J7\WpUtils\Performance::performance()
	 * @param array     $fn_array 函數
	 * - callback callable function
	 * - args array 參數
	 * @param int|null  $precision 精確度，預設 5
	 * @param bool|null $print_log 是否印出 log，預設 true
	 *
	 * @return array
	 * - return mixed
	 * - execution_time float
	 * @throws \Exception 不是可呼叫的函數
	 */
	public static function performance( array $fn_array, ?int $precision = 5, ?bool $print_log = true ): array {
		return \J7\WpUtils\Performance::performance( $fn_array, $precision, $print_log );
	}

	/**
	 * 檢查單前網址是否在特定關鍵字內
	 *
	 * @deprecated \J7\WpHelpers\Arr::in_url()
	 *
	 * @param array $keywords 關鍵字
	 * @return bool
	 */
	public static function in_url( array $keywords ): bool {
		return ( new \J7\WpHelpers\Arr( $keywords ) )->in_url();
	}

	/**
	 * 將 '[]' 轉為空數組，'true' 轉為 true，'false' 轉為 false。
	 *
	 * @deprecated \J7\WpHelpers\Arr::parse()
	 * @param array<array-key, mixed> $arr 原始數組。
	 * @return array<array-key, mixed> 轉換後的數組。
	 * @since 0.3.11
	 */
	public static function parse( array $arr ): array {
		return ( new \J7\WpHelpers\Arr( $arr ) )->parse()->to_array();
	}

	/**
	 * 格式化單層 key-value數組，將 '[]' 轉為空數組。
	 *
	 * 遍歷數組，將值為 '[]' 的項目轉換為空數組。
	 *
	 * @param array<array-key, mixed> $arr 原始數組。
	 * @return array<array-key, mixed> 轉換後的數組。
	 * @deprecated 0.3.10
	 */
	public static function format_empty_array( array $arr ): array {
		return self::parse( $arr );
	}



	/**
	 * 針對台灣地區網路環境優化的 IP 獲取函數
	 * 適用於: 中華電信/遠傳/台灣大哥大等 ISP 的光纖/4G/5G 網路
	 *
	 * @deprecated \J7\WpUtils\IP::get_client_ip()
	 * @return string|null
	 */
	public static function get_client_ip(): string|null {
		return \J7\WpUtils\IP::get_client_ip();
	}

	/**
	 * Array Find
	 *
	 * @deprecated \J7\WpHelpers\Arr::find()
	 *
	 * @param array<array-key, mixed> $array 陣列
	 * @param callable                $callback 回調函數
	 * @since 0.3.5
	 *
	 * @return mixed|null
	 */
	public static function array_find( array $array, callable $callback ) {
		return ( new \J7\WpHelpers\Arr( $array ) )->find( $callback );
	}

	/**
	 * 檢查陣列中是否至少有一個元素滿足條件
	 *
	 * @deprecated \J7\WpHelpers\Arr::some()
	 * @param array<array-key, mixed> $array 陣列
	 * @param callable                $callback 回調函數
	 * @since 0.3.5
	 *
	 * @return bool
	 */
	public static function array_some( array $array, callable $callback ): bool {
		return ( new \J7\WpHelpers\Arr( $array ) )->some( $callback );
	}

	/**
	 * 檢查陣列中所有元素是否都滿足條件
	 *
	 * @deprecated \J7\WpHelpers\Arr::every()
	 * @param array<array-key, mixed> $arr 陣列
	 * @param callable                $callback 回調函數
	 * @since 0.3.5
	 *
	 * @return bool
	 */
	public static function array_every( array $arr, callable $callback ): bool {
		return ( new \J7\WpHelpers\Arr( $arr ) )->every( $callback );
	}


	/**
	 * 解構陣列
	 *
	 * @deprecated \J7\WpHelpers\Arr::pick()
	 * @param array<array-key, mixed> $arr 陣列
	 * @param array<string>|string    $keys 要解構的 key
	 * @return array<array-key, mixed> 解構後的陣列，可以按照 $keys 順序解構取得，最後一個為 $rest 剩餘的 $array
	 */
	public static function destruct( array $arr, array|string $keys ): array {
		if (is_string($keys)) {
			$keys = [ $keys ];
		}

		return ( new \J7\WpHelpers\Arr( $arr ) )->pick( $keys );
	}

	/**
	 * 將新值轉換為與原值相同的型別
	 *
	 * @deprecated \J7\WpAbstracts\DTO::fit_type()
	 *
	 * @param mixed $value 原值
	 * @param mixed $new_value 新值
	 * @return mixed 轉換後的值
	 */
	public static function to_same_type( mixed $value, mixed $new_value ): mixed {
		$type = gettype($value);
		return match ($type) {
			'boolean' => (bool) $new_value,
			'integer' => (int) $new_value,
			'double' => (float) $new_value,  // float 在 gettype() 中顯示為 'double'
			'string' => (string) $new_value,
			'array' => (array) $new_value,
			'object' => (object) $new_value,
			'NULL' => null,
			default => $new_value, // 其他型別保持原樣
		};
	}
}
