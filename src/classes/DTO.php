<?php

namespace J7\WpUtils\Classes;

if ( class_exists( 'DTO' ) ) {
	return;
}

/** Class DTO 可能單例也可能多例，需要自行額外實現 instance() 方法 */
abstract class DTO {

	/** @var array<string,mixed> Raw data */
	protected array $dto_data = [];

	/** @var \WP_Error Error */
	protected \WP_Error $dto_error;

	/** @var static|null @deprecated 單例模式已經棄用 */
	protected static $dto_instance;

	/** @var array<string> 必須的屬性，如果沒有設定則會拋出錯誤 */
	protected array $require_properties = [];

	/** @var array<string, \ReflectionProperty[]> 靜態緩存各類別的屬性 */
	protected static array $reflection_cache = [];

	/**
	 * @param array<string,mixed> $input The data to set.
	 * @return void
	 * @throws \Exception If the property is not defined.
	 */
	public function __construct( array $input = [] ) {
		$this->dto_error = new \WP_Error();
		$strict          = false;
		if (function_exists('wp_get_environment_type')) {
			$strict =( 'local' === \wp_get_environment_type() );
		}
		try {
			$this->dto_data = $input;
			$this->before_init();
			foreach ( $input as $key => $value ) {
				if (property_exists($this, $key)) {
					$this->$key = $value;
				}
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
	 * 取得公開的屬性 array
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		$class_name = static::class;

		// 使用靜態緩存避免重複反射操作
		if (!isset(self::$reflection_cache[ $class_name ])) {
			$reflection                            = new \ReflectionClass($this);
			self::$reflection_cache[ $class_name ] = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
		}

		$props  = self::$reflection_cache[ $class_name ];
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
	 * 格式化 Value
	 * to_array 時要把值轉為 primitive 值
	 * 1. array 遞迴
	 * 2. 巢狀 DTO 要巢狀 to_array
	 * 3. 枚舉要轉為 value
	 *
	 * @param mixed $value The value to format.
	 *
	 * @return mixed
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
	 * Set dynamic property to container.
	 *
	 * @param string $property The property name.
	 * @param mixed  $value The property value.
	 *
	 * @return void
	 * @throws \Error SimpleDTOs are immutable
	 */
	public function __set( string $property, $value ): void {
		$this->dto_error->add( 'immutable', 'DTOs are immutable. Create a new DTO to set a new value.' );
	}

	/**
	 * Check if the property is defined.
	 *
	 * @param string $property The property name.
	 *
	 * @return bool
	 */
	public function __isset( string $property ): bool {
		return array_key_exists($property, $this->dto_data);
	}


	/** @param mixed $data Parse data to DTO  @return static */
	public static function parse( $data ): static {
		return new static($data); // @phpstan-ignore-line
	}

	/**
	 * Parse array to DTO array
	 * 例如 物件組成的 array
	 *
	 * @param array<string,mixed> $input The data to parse.
	 * @return array<int,static>
	 */
	public static function parse_array( array $input ): array {
		$arr = [];
		foreach ($input as $item) {
			$arr[] = static::parse($item);
		}
		return $arr;
	}

	/** @return void 初始化前的處理  */
	protected function before_init(): void {
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
