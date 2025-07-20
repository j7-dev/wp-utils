<?php

declare( strict_types = 1 );

namespace J7\WpHelpers;

/**
 * Database Table 操作方法
 */
class Table {

	/**
	 * 建構子
	 *
	 * @param string $table_name 資料庫 table 名稱
	 */
	public function __construct(
		/** @var string $table_name 資料庫 table 名稱，不包含 $wpdb->prefix 前綴 */
		public string $table_name,
	) {
	}

	/**
	 * 判斷資料庫 table 是否存在
	 * TODO 移除空格根斷行
	 *
	 * @return bool
	 */
	public function is_exists(): bool {
		global $wpdb;
		$exists = $wpdb->get_var(
		$wpdb->prepare(
		'SELECT EXISTS (
            SELECT 1 FROM information_schema.tables
            WHERE table_schema = %s
            AND table_name = %s
        )',
		DB_NAME,
		"{$wpdb->prefix}{$this->table_name}"
		)
		);
		return (bool) $exists;
	}
}
