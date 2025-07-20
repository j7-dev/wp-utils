<?php
/**
 * UniqueArray class
 * 去重 Array
 */

declare( strict_types = 1 );

namespace J7\WpUtils\Classes;

if ( class_exists( 'UniqueArray' ) ) {
	return;
}

/**
 * UniqueArray class
 *
 * @deprecated \J7\WpHelpers\Arr::remove_duplicates()
 *
 * @package J7\WpUtils
 */
final class UniqueArray {

	/**
	 * 是否嚴格模式
	 *
	 * @var bool
	 */
	private $strict = false;

	/**
	 * 陣列
	 *
	 * @var array $list
	 */
	private $list;

	/**
	 * 建構子
	 *
	 * @param array $the_list 陣列
	 * @param bool  $strict 是否嚴格模式
	 */
	public function __construct( array $the_list, ?bool $strict = false ) {
		$this->strict = $strict;
		$this->list   = self::remove_duplicates( $the_list, $strict );
	}

	/**
	 * 新增一個值
	 *
	 * @param mixed $new_value 值
	 * @return UniqueArray
	 */
	public function add( $new_value ): UniqueArray {
		return new UniqueArray( array_merge( $this->get_list(), [ $new_value ] ) );
	}

	/**
	 * 移除一個值
	 *
	 * @param mixed $value 值
	 * @return UniqueArray
	 */
	public function remove( $value ): UniqueArray {
		$list       = $this->list;
		$list_count = count( $list );
		for ( $i = 0; $i < $list_count; $i++ ) {
			if ( $list[ $i ] === $value ) {
				unset( $list[ $i ] );
				$list = array_values( $list ); // Since we've unset value, we need to resort array so there are no index holes.
				break; // Since we don't allow duplicates, we only need to check for value once.
			}
		}
		return new UniqueArray( $list );
	}

	/**
	 * 新增多個值
	 *
	 * @param array $add_list 值
	 * @return UniqueArray
	 */
	public function add_list( array $add_list ): UniqueArray {
		return new UniqueArray( array_merge( $this->list, $add_list ) );
	}

	/**
	 * 移除多個值
	 *
	 * @param array $remove_list 值
	 * @return UniqueArray
	 */
	public function remove_list( array $remove_list ): UniqueArray {
		$list = [];
		foreach ( $this->list as $item ) {
			if ( !in_array( $item, $remove_list, $this->strict ) ) {
				$list[] = $item;
			}
		}
		return new UniqueArray( $list );
	}

	/**
	 * 取得陣列
	 *
	 * @return array
	 */
	public function get_list(): array {
		return $this->list;
	}

	/**
	 * 移除重複值
	 *
	 * @param array $the_list 陣列
	 * @param bool  $strict 是否嚴格模式
	 * @return array
	 */
	private static function remove_duplicates( array $the_list, ?bool $strict = false ): array {
		$new_list = [];
		foreach ( $the_list as $item ) {
			if ( !in_array( $item, $new_list, $strict ) ) {
				$new_list[] = $item;
			}
		}
		return $new_list;
	}
}
