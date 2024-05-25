<?php
/**
 * DB class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'DB' ) ) {
	return;
}
/**
 * DB class
 */
abstract class DB {

	/**
	 * Delete post meta by meta id
	 * 刪除指定的 meta id 的 post meta
	 *
	 * @param int $mid - meta id
	 * @return bool - 是否刪除成功
	 */
	public static function delete_post_meta_by_mid( $mid ) {
		global $wpdb;

		// 执行删除查询
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_id = %d", $mid ) );

		$delete_success = $deleted !== false;

		return $delete_success;
	}
}
