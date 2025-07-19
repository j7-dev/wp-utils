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

	/** @return array 取得原始陣列 */
	public function to_array(): array {
		return $this->items;
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
}
