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
	 *
	 * @return void
	 */
	public function __construct( array $input = [] ) {
		$this->data = $input;
		foreach ( $input as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Get the data as an array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return $this->data;
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
			throw new \Error("Undefined property: {$self}::\$$property.");
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
}
