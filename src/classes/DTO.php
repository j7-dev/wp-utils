<?php
/**
 * DTO class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'DTO' ) ) {
	return;
}

/**
 * Class DTO 可能單例也可能多例，需要自行額外實現 instance() 方法
 */
abstract class DTO {

	/**
	 * Raw data
	 *
	 * @var array<string,mixed>
	 */
	private $data = [];

	/**
	 * Constructor
	 *
	 * @param array<string,mixed> $input The data to set.
	 * @param bool                $strict Whether to throw an error if the property is not defined.
	 *
	 * @return void
	 * @throws \Error If the property is not defined and strict is true.
	 */
	public function __construct( array $input = [], bool $strict = true ) {
		$this->data = $input;
		foreach ( $input as $key => $value ) {
			if (!property_exists($this, $key)) {
				$class_name = static::class;
				$message    = "Undefined property: {$class_name}::\${$key}.";
				if ($strict) {
					throw new \Error($message); // phpcs:ignore
				}
				error_log($message);
			}
			$this->$key = $value;
		}
	}

	/**
	 * 取得公開的屬性 array
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		$reflection = new \ReflectionClass($this);
		$props      = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

		$result = [];
		foreach ($props as $prop) {
			$result[ $prop->getName() ] = $prop->getValue($this);
		}
		return $result;
	}


	/**
	 * Get dynamic property from container.
	 *
	 * @param string $property The property name.
	 *
	 * @return mixed The property value.
	 * @throws \Error If the property is not defined.
	 */
	public function __get(string $property ) { // phpcs:ignore
		if (!$this->__isset($property)) {
			$self = static::class;
			throw new \Error("Undefined property: {$self}::\$$property."); // phpcs:ignore
		}

		return $this->data[ $property ];
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
		throw new \Error('SimpleDTOs are immutable. Create a new DTO to set a new value.');
	}

	/**
	 * Check if the property is defined.
	 *
	 * @param string $property The property name.
	 *
	 * @return bool
	 */
	public function __isset( string $property ): bool {
		return array_key_exists($property, $this->data);
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
}
