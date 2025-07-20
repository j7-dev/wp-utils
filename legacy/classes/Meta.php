<?php
/**
 * Meta class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'Meta' ) ) {
	return;
}

/**
 * Meta class
 */
final class Meta {

	/**
	 * Container for dynamic properties.
	 *
	 * @var array
	 */
	protected $container = [
		'id'       => null,
		'resource' => null,
	];

	/**
	 * Constructor
	 *
	 * @param int     $id       The ID of the resource.
	 * @param ?string $resource The type of resource.
	 */
	public function __construct( int $id, ?string $resource = 'post' ) {
		$this->container['id']       = $id;
		$this->container['resource'] = $resource;
	}

	/**
														* Get the callable function name.
														*
														* @param string $operation 操作類型，預設為 'update'，也可以是 'get' 或 'delete', 'add'
													 *
													 * @return string|array
													 */
	final protected function get_meta_callable( $operation = 'update' ): string|array {
		$resource = $this->__get('resource');
		return match ($resource) {
			'post' => "{$operation}_post_meta",
			'term' => "{$operation}_term_meta",
			'user' => "{$operation}_user_meta",
			default => "{$operation}_post_meta"
		};
	}




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

	/**
													 * 取得 array 的 meta value
													 *
													 * @param string $name meta key
													 *
													 * @return array $items
													 * @throws \Exception 當 id 未設定時
													 */
	final public function get_array( string $name ): array {
		$id = $this->__get('id');
		if (!$id) {
			throw new \Exception('id is not set');
		}
		$callable     = $this->get_meta_callable('get');
		$items        = (array) \call_user_func( $callable, $id, $name );
		$unique_items = array_unique( $items, SORT_REGULAR );
		return $unique_items;
	}

	/**
													 * 添加 array 的 meta value
													 *
													 * @param string $name meta key
													 * @param array  $ids_to_add meta value
													 *
													 * @return void
													 * @throws \Exception 當 id 未設定時
													 */
	final public function add_array( string $name, array $ids_to_add ): void {
		$id = $this->__get('id');
		if (!$id) {
			throw new \Exception('id is not set');
		}

		$items        = $this->get_array($name);
		$callable     = $this->get_meta_callable('add');
		$items_to_add = [];
		foreach ($ids_to_add as $id_to_add) {
			if ( in_array(  $id_to_add, $items ) ) {
				continue;
			}
			$items_to_add[] = $id_to_add;
		}

		foreach ($items_to_add as $item_to_add) {
			\call_user_func( $callable, $id, $name, $item_to_add );
		}
	}

	/**
													 * 更新 array 的 meta value
													 *
													 * @param string $name meta key
													 * @param array  $ids_to_update meta value
													 *
													 * @return void
													 * @throws \Exception 當 id 未設定時
													 */
	final public function update_array( string $name, array $ids_to_update ): void {
		$id = $this->__get('id');
		if (!$id) {
			throw new \Exception('id is not set');
		}

		$delete_callable = $this->get_meta_callable('delete');
		\call_user_func($delete_callable, $id, $name);

		$this->add_array($name, $ids_to_update);
	}


	/**
													 * 刪除 array 的 meta value
													 *
													 * @param string $name meta key
													 * @param array  $ids_to_delete meta value
													 *
													 * @return void
													* @throws \Exception 當 id 未設定時
													*/
	final public function delete_array( string $name, array $ids_to_delete ): void {
		$id = $this->__get('id');
		if (!$id) {
			throw new \Exception('id is not set');
		}

		$delete_callable = $this->get_meta_callable('delete');
		foreach ($ids_to_delete as $id_to_delete) {
			\call_user_func($delete_callable, $id, $name, $id_to_delete);
		}
	}
}
