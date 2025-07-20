<?php

namespace J7\WpAbstracts;

use J7\WpUtils\Classes\WC;

/** DTO 類別 可能單例也可能多例，需要自行額外實現 instance() 或 create() 方法 */
abstract class DTO {



	/** @var \WP_Error 錯誤物件 */
	protected \WP_Error $dto_error;

	/** @var array<string> 必須的屬性，如果沒有設定則會拋出錯誤 */
	protected array $require_properties = [];

	/** @var array<string, \ReflectionProperty[]> 靜態緩存各類別的屬性 */
	protected static array $reflection_properties_cache = [];

	/** @var ReflectionClass|null 靜態緩存各類別的反射 */
	protected static ?\ReflectionClass $reflection_class_cache = null;

	/** @var bool 是否不可變 */
	protected bool $immutable = true;

	/**
	 * 建構函式
	 *
	 * @param array<string,mixed> $input 要設定的資料
	 * @param bool                $auto_fit_type 是否自動轉換型別
	 * @return void
	 * @throws \Exception 如果屬性未定義則拋出例外
	 */
	protected function __construct( array $input = [], $auto_fit_type = false ) {
		$this->dto_error = new \WP_Error();
		$strict          = false;
		if (function_exists('wp_get_environment_type')) {
			$strict =( 'local' !== \wp_get_environment_type() );
		}
		try {
			$this->before_init();
			foreach ( $input as $key => $value ) {
				if (!property_exists($this, $key)) {
					$class_name = static::class;
					$this->dto_error->add( 'invalid_property', "Try to set undefined property: {$class_name}::\${$key}." );
				}
				$this->$key = $auto_fit_type ? $this->fit_type( $this->$key, $value ) : $value;
			}
			$this->validate();
			$this->after_init();
			if ( $this->dto_error->has_errors() ) {
				throw new \Exception(implode("\n", $this->dto_error->get_error_messages())); // phpcs:ignore
			}
		} catch (\Throwable $th) {
			$error_messages = $th->getMessage();
			// 如果嚴格模式，則拋出錯誤
			if ( $strict ) {
				throw new \Exception($error_messages); // phpcs:ignore
			}
			// 如果有錯誤，則記錄錯誤
			WC::logger(
				'DTO Error ' . $error_messages,
				'error',
				[],
				'dto'
				);
		}
	}

	/**
	 * 取得公開的屬性陣列
	 *
	 * @return array<string,mixed> 屬性陣列
	 */
	public function to_array(): array {

		// 使用靜態緩存避免重複反射操作
		if (null === self::$reflection_properties_cache) {
			$reflection                        = new \ReflectionClass($this);
			self::$reflection_properties_cache = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
		}

		$props  = self::$reflection_properties_cache;
		$result = [];

		foreach ($props as $prop) {
			// 如果沒被初始化就跳過
			if (!$prop->isInitialized($this)) {
				continue;
			}

			$value                = $prop->getValue($this);
			$prop_name            = $prop->getName();
			$result[ $prop_name ] = $this->get_formatted_value($value);
		}

		return $result;
	}

	/**
	 * 格式化數值
	 * to_array 時要把值轉為基本型別值
	 * 1. 陣列遞迴處理
	 * 2. 巢狀 DTO 要巢狀呼叫 to_array
	 * 3. 枚舉要轉為 value
	 *
	 * @param mixed $value 要格式化的值
	 *
	 * @return mixed 格式化後的值
	 */
	private function get_formatted_value( mixed $value ): mixed {
		if (is_array($value)) {
			return array_map(
			fn ( $item ) => $this->get_formatted_value($item),
			$value
			);
		}

		if ($value instanceof self) {
			return $value->to_array();
		}

		if ($value instanceof \BackedEnum) {
			return $value->value;
		}

		return $value;
	}

	/**
	 * 設定動態屬性到容器
	 *
	 * @param string $property 屬性名稱
	 * @param mixed  $value 屬性值
	 *
	 * @return void
	 * @throws \Error DTO 是不可變的
	 */
	public function __set( string $property, $value ): void {
		if ($this->immutable) {
			$this->dto_error->add( 'immutable', "嘗試把 {$property} 設定為 {$value}，但 DTO 是不可變的" );
		}
		if ( !property_exists( $this, $property ) ) {
			$this->dto_error->add( 'invalid_property', "屬性 {$property} 不存在" );
		}
		$this->$property = $value;
	}

	/**
	 * 檢查屬性是否已定義
	 *
	 * @param string $property 屬性名稱
	 *
	 * @return bool 屬性是否存在
	 */
	public function __isset( string $property ): bool {
		return property_exists( $this, $property );
	}

	/** @param mixed $data 從陣列轉換成 DTO  @return static */
	public static function from( $data ): static {
		return new static($data); // @phpstan-ignore-line
	}


	/** @return void 初始化前的處理  */
	protected function before_init(): void {
	}

	/**
	 * 轉換型別
	 *
	 * @param string $property 屬性名稱
	 * @param mixed  $value 屬性值
	 *
	 * @return mixed 轉換後的值
	 */
	protected function fit_type( string $property, mixed $value ): mixed {
		if (null ===self::$reflection_class_cache) {
			$reflection                   = new \ReflectionClass(static::class);
			self::$reflection_class_cache = $reflection;
		}

		$props = self::$reflection_class_cache->getProperty($property);
		$type  = $props->getType();
		return match ($type) {
			'bool' => (bool) $value,
			'int' => (int) $value,
			'float' => (float) $value,
			'string' => (string) $value,
			'array' => (array) $value,
			'NULL' => null,
			default => $value, // 其他型別保持原樣
		};
	}

	/** @return void 初始化後的處理 */
	protected function after_init(): void {
	}

	/**
	 * @return void 驗證 DTO
	 * @throws \Exception 如果驗證失敗則拋出錯誤
	 * */
	protected function validate(): void {
		foreach ($this->require_properties as $property) {
			if (!isset($this->$property)) {
				throw new \Exception("Property {$property} is required.");
			}
		}
	}
}
