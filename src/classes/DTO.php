<?php

namespace J7\WpUtils\Classes;

use J7\WpUtils\Classes\General;

if ( class_exists( 'DTO' ) ) {
	return;
}

/** Class DTO 可能單例也可能多例，需要自行額外實現 instance() 方法 */
abstract class DTO {

	/** @var array<string,mixed> Raw data */
	protected array $dto_data = [];

	/** @var \WP_Error Error */
	protected \WP_Error $dto_error;

	/** @var static|null DTO instance 可以做單例或工廠，預設為 null，單例需要自己實現 instance() 方法 */
	protected static $dto_instance;

	/** @var array<string> 必須的屬性，如果沒有設定則會拋出錯誤 */
	protected array $require_properties = [];

	/** @var array<string, \ReflectionProperty[]> 靜態緩存各類別的屬性 */
	protected static array $reflection_cache = [];

	/**
	 * Constructor
	 *
	 * @param array<string,mixed> $input The data to set.
	 * @param ?bool               $strict 嚴格模式
	 *
	 * @return void
	 * @throws \Error If the property is not defined and strict is true.
	 */
	public function __construct( array $input = [], ?bool $strict = null ) {

		$this->dto_error = new \WP_Error();
		$this->dto_data  = $input;
		$this->before_init();
		foreach ( $input as $key => $value ) {
			if (!property_exists($this, $key)) {
				$class_name = static::class;
				$this->dto_error->add( 'invalid_property', "Try to set undefined property: {$class_name}::\${$key}." );
			}
			$this->$key = $value;
		}
		$this->validate();
		$this->after_init();

		if (!$this->dto_error->has_errors()) {
			return;
		}

		// 如果有錯誤，則記錄錯誤
		$error_messages = $this->dto_error->get_error_messages();
		WC::logger(
				'DTO Error ',
				'error',
				[
					'error_messages' => $error_messages,
				],
				'dto'
				);
		// 如果嚴格模式，則拋出錯誤

		if (function_exists('wp_get_environment_type')) {
			$strict = $strict ?? ( 'local' !== \wp_get_environment_type() );
		} else {
			$strict = $strict ?? false;
		}

		if ( $strict ) {
				throw new \Error(implode("\n", $error_messages)); // phpcs:ignore
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

			$value     = $prop->getValue($this);
			$prop_name = $prop->getName();

			// 如果是巢狀的 DTO 則遞歸執行 to_array
			if ($value instanceof self) {
				$result[ $prop_name ] = $value->to_array();
				continue;
			}

			// 如果是巢狀的 DTO array 則遞歸執行 to_array
			if (is_array($value)) {
				// 檢查是否為空數組或第一個元素是否為 DTO
				if (!empty($value) && reset($value) instanceof self) {
					// 使用 array_map 直接處理，避免 General::array_every 的額外開銷
					$dto_array = [];
					foreach ($value as $item) {
						if ($item instanceof self) {
							$dto_array[] = $item->to_array();
						} else {
							// 如果陣列中有非 DTO 項目，則使用原始處理方式
							$result[ $prop_name ] = $value;
							continue 2;
						}
					}
					$result[ $prop_name ] = $dto_array;
					continue;
				}
			}

			$result[ $prop_name ] = $value;
		}

		return $result;
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
		// dto_instance 是可以被賦值得，例如單例模式如果要 invalidate 可以將 dto_instance 設為 null
		if ('dto_instance' === $property) {
			static::$dto_instance = $value; // @phpstan-ignore-line
			return;
		}
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


	/**
	 * Parse data to DTO
	 *
	 * @param mixed $data The data to parse.
	 * @param bool  $strict Whether to throw an error if the property is not defined.
	 *
	 * @return static
	 */
	public static function parse( mixed $data, ?bool $strict = true ): static {
		return new static($data, $strict); // @phpstan-ignore-line
	}

	/**
	 * Parse array to DTO array
	 * 例如 物件組成的 array
	 *
	 * @param array<string,mixed> $input The data to parse.
	 * @param bool                $strict Whether to throw an error if the property is not defined.
	 *
	 * @return array<int,static>
	 */
	public static function parse_array( array $input, ?bool $strict = true ): array {
		return array_values(
		array_map(
			fn ( $item ) => static::parse($item, $strict),
			$input
		)
		);
	}

	/** @return void 初始化前的處理  */
	protected function before_init(): void {
	}

	/** @return void 初始化後的處理 */
	protected function after_init(): void {
	}

	/**  @return void 驗證 DTO */
	protected function validate(): void {
		foreach ($this->require_properties as $property) {
			if (!isset($this->$property)) {
				$this->dto_error->add( 'validate_failed', "Property {$property} is required." );
			}
		}
	}
}
