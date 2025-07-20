<?php

declare( strict_types = 1 );

namespace J7\WpUtils;

/**
 * Performance 工具
 */
abstract class Performance {
	/**
	 * 測試函數執行時間
	 *
	 * @param array{
	 *  callback callable function
	 *  args array<int, mixed> 參數
	 * }     $fn 函數
	 * @param int    $precision 精確度，預設 5
	 * @param bool   $print_log 是否印出 log，預設 true
	 *
	 * @return array{
	 *  return mixed
	 *  execution_time float
	 *  function_name string
	 * }
	 * @throws \Exception 不是可呼叫的函數
	 */
	public static function performance( callable $fn_array, int $precision = 5, bool $print_log = true ): array {
		[
			'callback' => $callback,
			'args'     => $args,
		] = $fn_array;

		$function_name = is_array( $callback ) ? "[{$callback[0]}, {$callback[1]}]" : $callback;

		if ( ! is_callable( $callback ) ) {
			throw new \Exception( "{$function_name} 不是可呼叫的函數" );
		}

		$start = microtime( true );

		$fn_return = call_user_func( $callback, ...$args );

		$end            = microtime( true );
		$execution_time = round( ( $end - $start ), $precision );

		if ( $print_log ) {
			\J7\WpUtils\Classes\WC::logger(
				sprintf(
				'Performance: 執行: %1$s 花費時間: %2$s 秒',
				$function_name,
				$execution_time
			),
				'debug',
			[
				'return' => $fn_return,
				] // phpcs:ignore
				);
		}

		return [
			'return'         => $fn_return,
			'execution_time' => $execution_time,
			'function_name'  => $function_name,
		];
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
	 * @throws \Exception 不是可呼叫的函數
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

		\J7\WpUtils\Classes\WC::logger(
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
			),
			'debug',
			[
				'from' => $from_result,
				'to'   => $to_result,
			]
		);

		return [
			'from'                => $from_result,
			'to'                  => $to_result,
			'execution_time_diff' => $execution_time_diff,
		];
	}
}
