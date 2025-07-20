<?php

declare( strict_types = 1 );

namespace J7\WpHelpers;

/**
 * Array 操作方法
 * 提供 some, every, find 等方法
 * filter, map 方法可以鏈式調用
 */
class Arr {

	/**
	 * 建構子
	 *
	 * @param array $items 陣列
	 * @param bool  $strict 是否嚴格模式
	 */
	public function __construct(
		/** @var array $list 陣列 */
		public array $items,
		/** @var bool 是否嚴格模式 */
		protected bool $strict = false
	) {
	}

	/**
	 * 靜態建立方法
	 *
	 * @param array $items 陣列
	 * @param bool  $strict 是否嚴格模式
	 * @return static
	 */
	public static function create( array $items, bool $strict = false ): static {
		return new static( $items, $strict );
	}

	/**
	 * 檢查陣列中是否至少有一個元素滿足條件
	 *
	 * @param callable $callback 回調函數 (value, key, array) => bool
	 * @return bool
	 */
	public function some( callable $callback ): bool {
		foreach ( $this->items as $key => $value ) {
			if ( $callback( $value, $key, $this->items ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 檢查陣列中所有元素是否都滿足條件
	 *
	 * @param callable $callback 回調函數 (value, key, array) => bool
	 * @return bool
	 */
	public function every( callable $callback ): bool {
		foreach ( $this->items as $key => $value ) {
			if ( ! $callback( $value, $key, $this->items ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 找到第一個滿足條件的元素
	 *
	 * @param callable $callback 回調函數 (value, key, array) => bool
	 * @return mixed|null 找到的元素，沒找到則返回 null
	 */
	public function find( callable $callback ): mixed {
		foreach ( $this->items as $key => $value ) {
			if ( $callback( $value, $key, $this->items ) ) {
				return $value;
			}
		}
		return null;
	}

	/**
	 * 找到第一個滿足條件的元素的索引
	 *
	 * @param callable $callback 回調函數 (value, key, array) => bool
	 * @return int|string|null 找到的索引，沒找到則返回 null
	 */
	public function find_index( callable $callback ): int|string|null {
		foreach ( $this->items as $key => $value ) {
			if ( $callback( $value, $key, $this->items ) ) {
				return $key;
			}
		}
		return null;
	}

	/**
	 * 過濾陣列元素
	 *
	 * @param callable $callback 回調函數 (value, key, array) => bool
	 * @return static 新的 Arr 實例
	 */
	public function filter( callable $callback ): static {
		$this->items = array_filter( $this->items, $callback );
		return $this;
	}

	/**
	 * 映射陣列元素
	 *
	 * @param callable $callback 回調函數 (value, key, array) => mixed
	 * @return static 新的 Arr 實例
	 */
	public function map( callable $callback ): static {
		$this->items = array_map( $callback, $this->items );
		return $this;
	}

	/**
	 * 歸納陣列元素
	 *
	 * @param callable $callback 回調函數 (accumulator, value, key, array) => mixed
	 * @param mixed    $initial 初始值
	 * @return mixed
	 */
	public function reduce( callable $callback, mixed $initial = null ): mixed {
		return array_reduce( $this->items, $callback, $initial );
	}

	/**
	 * 檢查陣列中是否包含指定值
	 *
	 * @param mixed $search 要搜索的值
	 * @return bool
	 */
	public function includes( mixed $search ): bool {
		return in_array( $search, $this->items, $this->strict );
	}

	/** @return int 取得陣列長度 */
	public function length(): int {
		return count( $this->items );
	}

	/** @return mixed|null 取得第一個元素 */
	public function first(): mixed {
		return empty( $this->items ) ? null : reset( $this->items );
	}

	/** @return mixed|null 取得最後一個元素 */
	public function last(): mixed {
		return empty( $this->items ) ? null : end( $this->items );
	}

	/** @return array 取得陣列 */
	public function to_array(): array {
		return $this->items;
	}

	/** @return string 取得陣列字串 */
	public function to_string( string $separator = ',' ): string {
		return implode( $separator, $this->items );
	}

	/** @return array 取得陣列的值（重新索引） */
	public function values(): array {
		return array_values( $this->items );
	}

	/** @return array 取得陣列的鍵  */
	public function keys(): array {
		return array_keys( $this->items );
	}

	/** @return bool 判斷陣列是否為空  */
	public function is_empty(): bool {
		return empty( $this->items );
	}

	/**
	 * 將陣列轉換為 HTML 表格
	 *
	 * @param array{
	 *  title?: string,
	 *  br?: bool,
	 * } $options 選項
	 * @return string
	 */
	public function to_html( array $options = [] ): string {
		@[ // @phpstan-ignore-line
		'title' => $title,
		'br' => $br, // 是否使用 <br> 不使用 table
		] = $options;

		$html = '';

		if ( $title ) {
			$html .= "<p><strong>{$title}</strong></p>";
		}
		if (!$this->items) {
			return $html;
		}

		$html .= $br ? '' : '<table style="width: 100%;font-size: 12px;border-collapse: collapse;">';
		foreach ( $this->items as $key => $value ) {
			$value_stringify = match (gettype($value)) {
				'array' => '<pre style="font-size: 10px;">' . \print_r($value, true) . '</pre>',
				'object' => $value instanceof \stdClass ? ( '<pre style="font-size: 10px;">' . \print_r($value, true) . '</pre>' ) : $value::class,
				'boolean' => $value ? 'true' : 'false',
				'NULL' => 'null',
				default => (string) $value,
			};

			if ( $br ) {
				$html .= "{$key}: {$value_stringify}<br>";
				continue;
			}
			$html .= '<tr style="border-bottom: 1px solid #777;">';
			$html .= "<th style='vertical-align: top;padding-right: 4px;'>{$key}</th>";
			$html .= "<td style='word-break: break-all;vertical-align: top;white-space: normal;'>{$value_stringify}</td>";
			$html .= '</tr>';
		}

		$html .= $br ? '' : '</table>';

		return $html;
	}

	/**
	 * 傳入的 array 比初始 array 少了那些元素
	 *
	 * @example
	 * $arr = Arr::create([1, 2, 3, 4, 5]);
	 * $arr->diff([1, 2, 3]);
	 * // [4, 5]
	 *
	 *  $arr = Arr::create([1, 2, 3]);
	 *  $arr->diff([1, 2, 3, 4, 5]);
	 *  // []
	 *
	 * @param array $compared_arr 比較的陣列
	 * @return static 新的 Arr 實例
	 */
	public function diff( array $compared_arr ): static {
		$this->items = array_diff( $this->items, $compared_arr );
		return $this;
	}

	/**
	 * 插入元素到指定的鍵值中
	 *
	 * @param array              $item 插入的陣列
	 * @param 'before' | 'after' $position 插入位置
	 * @param string|int         $item_key 插入的鍵
	 * @return static 新的 Arr 實例
	 */
	public function insert( array $item, $position = 'after', $item_key = '' ): static {
		$new_items = [];
		foreach ( $this->items as $key => $value ) {
			if ( $position === 'before' && $key === $item_key ) {
				$new_items[] = $item;
			}
			$new_items[] = $value;
			if ( $position === 'after' && $key === $item_key ) {
				$new_items[] = $item;
			}
		}
		$this->items = $new_items;
		return $this;
	}

	/**
	 * 將元素插入到陣列的末尾
	 *
	 * @param array $item 插入的 item
	 * @return static 新的 Arr 實例
	 */
	public function push( array $item ): static {
		$this->items[] = $item;
		return $this;
	}

	/**
	 * 將元素插入到陣列的頭部
	 *
	 * @param array $item 插入的 item
	 * @return static 新的 Arr 實例
	 */
	public function unshift( array $item ): static {
		$this->items = array_merge( [ $item ], $this->items );
		return $this;
	}

	/**  @return static 將陣列最後一個元素移除 */
	public function pop(): static {
		array_pop( $this->items );
		return $this;
	}

	/** @return static 將陣列第一個元素移除 */
	public function shift(): static {
		array_shift( $this->items );
		return $this;
	}

	/**
	 * Sanitize Array
	 * Sanitize 一個深層的關聯陣列
	 *
	 * @param bool          $allow_br 是否允許換行 \n \r，預設為 true，如果為 false，則會用 sanitize_text_field
	 * @param array<string> $skip_keys - 要跳過的 key，如果符合就不做任何 sanitize
	 * @return static
	 */
	public function sanitize( bool $allow_br = true, array $skip_keys = [] ): static {
		$this->items = self::sanitize_text_field_deep( $this->items, $allow_br, $skip_keys );
		return $this;
	}

	/**
	 * Sanitize Array
	 * Sanitize 一個深層的關聯陣列
	 *
	 * @param mixed         $value Value to sanitize
	 * @param bool          $allow_br 是否允許換行 \n \r，預設為 true，如果為 false，則會用 sanitize_text_field
	 * @param array<string> $skip_keys - 要跳過的 key，如果符合就不做任何 sanitize
	 * @return array<string, mixed>|string
	 */
	public static function sanitize_text_field_deep( $value, $allow_br = true, $skip_keys = [] ) {
		if ( is_array( $value ) ) {
			// if array, sanitize each element
			foreach ( $value as $key => $item ) {
				if ( in_array( $key, $skip_keys, true ) ) {
					continue;
				}
				$value[ $key ] = self::sanitize_text_field_deep( $item );
			}
			return $value;
		}

		// if not array, sanitize the value
		if ( $allow_br ) {
			/** @var string $value */
			return \sanitize_textarea_field( $value );
		} else {
			/** @var string $value */
			return \sanitize_text_field( $value );
		}
	}

	/**
	 * 通用批次處理高階函數
	 *
	 * @param callable $callback 處理每個項目的回調函數，接收項目和索引參數，回傳布林值表示成功或失敗
	 * @param array{
	 *  batch_size: int,
	 *  pause_ms: int,
	 *  flush_cache: bool,
	 * }    $options 設定選項
	 * @return array 處理結果統計
	 * @throws \Throwable 如果處理過程中發生錯誤，則拋出 \Throwable 異常
	 */
	public function batch_process( callable $callback, array $options = [] ): array {
		$default_options = [
			'batch_size'   => 50,  // 每批次處理的項目數量
			'pause_ms'     => 750, // 每批次之間暫停的毫秒數
			'flush_cache'  => true, // 每批次後是否清除 WordPress 快取
			'memory_limit' => '128M', // 記憶體限制
		];
		// 記住原本的記憶體限制
		$original_limit = ini_get('memory_limit');

		ini_set('memory_limit', $options['memory_limit']); // phpcs:ignore

		try {

			// 合併選項
			$options = \wp_parse_args( $options, $default_options );

			// 初始化結果統計
			$result = [
				'total'        => count($this->items),
				'success'      => 0,
				'failed'       => 0,
				'failed_items' => [],
			];

			// 分批處理
			$batches = array_chunk($this->items, $options['batch_size']);

			foreach ($batches as $batch_index => $batch) {
				// 處理每一批
				foreach ($batch as $index => $item) {
					$success = call_user_func($callback, $item, $index);

					if ($success) {
						++$result['success'];
					} else {
						++$result['failed'];
						$result['failed_items'][] = $item;
					}
				}

				// 如果不是最後一批，執行批次間操作
				if ($batch_index < count($batches) - 1) {
					// 清除快取，釋放記憶體
					if ($options['flush_cache']) {
						\wp_cache_flush();
					}

					// 暫停指定時間
					if ($options['pause_ms'] > 0) {
						usleep($options['pause_ms'] * 1000); // 轉換為微秒
					}
				}
			}
			// 恢復原本的記憶體限制
			ini_set('memory_limit', $original_limit); // phpcs:ignore

			return $result;
		} catch (\Throwable $th) {
			ini_set('memory_limit', $original_limit); // phpcs:ignore
			throw $th;
		}
	}

	/**
	 * @return bool 檢查單前網址是否在特定關鍵字內
	 * @throws \Exception 如果 Arr::$items 參數不是 array<string>
	 */
	public function in_url(): bool {
		if ($this->some(fn( $item ) => !is_string($item))) {
			throw new \Exception('Arr::$items 參數必須都是 array<string>');
		}

		$request_uri = $_SERVER['REQUEST_URI'] ?? ''; // phpcs:ignore
		if (!$request_uri) {
			return false;
		}
		$in_url = false;

		foreach ($this->items as $keyword) {
			if (strpos($request_uri, $keyword) !== false) {
				$in_url = true;
				break;
			}
		}
		return $in_url;
	}


	/**
	 * 將 '[]' 轉為空數組，'true' 轉為 true，'false' 轉為 false。
	 *
	 * @param mixed|null $match_value 要匹配的值，可以自訂 match 的值
	 * @return static 新的 Arr 實例
	 */
	public function parse( $match_value = null ): static {
		$formatted_array = [];
		foreach ($this->items as $key => $value) {
			$new_value = null === $match_value ? match ($value) {
				'[]' => [],
				'true' => true,
				'false' => false,
				default => $value,
			} : $match_value;

			$formatted_array[ $key ] = $new_value;
		}
		$this->items = $formatted_array;
		return $this;
	}

	/**
	 * 移除指定的 key
	 *
	 * @param array<string> $keys 要移除的 key
	 * @return static 新的 Arr 實例
	 */
	public function rest( array $keys ): static {
		return array_diff_key( $this->items, array_flip( $keys ) );
	}

	/**
	 * 選擇指定的 key
	 *
	 * @param array<string> $keys 要選擇的 key
	 * @return array 選擇的 key 和剩下的 key
	 * @example
	 * $arr = Arr::create([
	 *  'a' => 1,
	 *  'b' => 2,
	 *  'c' => 3,
	 *  'd' => 4,
	 *  'e' => 5,
	 * ]);
	 * [$a, $b, $rest] = $arr->pick(['a', 'b']);
	 * // [ 1, 2, [ 'c' => 3, 'd' => 4, 'e' => 5 ] ]
	 */
	public function pick( array $keys ): array {
		$picked = [];
		foreach ($keys as $key) {
			$picked[] = $this->items[ $key ] ?? null;
		}
		return [ ...$picked, $this->rest( $keys ) ];
	}

	/**
	 * 移除重複值
	 *
	 * @param bool $strict 是否嚴格模式
	 * @return static
	 */
	public function remove_duplicates( bool $strict = false ): static {
		$new_list = [];
		foreach ( $this->items as $item ) {
			if ( !in_array( $item, $new_list, $strict ) ) {
				$new_list[] = $item;
			}
		}
		$this->items = $new_list;
		return $this;
	}
}
