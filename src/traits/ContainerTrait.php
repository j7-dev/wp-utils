<?php
/**
 * Container trait
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Traits;

if ( trait_exists( 'ContainerTrait' ) ) {
	return;
}

trait ContainerTrait {

	/**
	 * Container for dynamic properties.
	 *
	 * @var array
	 */
	protected $container = array();

	/**
	 * Get dynamic property from container.
	 *
	 * @param string $name The property name.
	 *
	 * @return mixed The property value.
	 */
	public function __get( $name ) { // phpcs:ignore
		if ( isset( $this->container[ $name ] ) ) {
			return $this->container[ $name ];
		}

		return null;
	}

	/**
	 * Set dynamic property to container.
	 *
	 * @param string $name The property name.
	 * @param mixed  $value The property value.
	 *
	 * @return void
	 */
	public function __set( $name, $value ) { // phpcs:ignore
		$this->container[ $name ] = $value;
	}
}
