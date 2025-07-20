<?php

declare( strict_types = 1 );

namespace J7\WpHelpers;

/**
 * Database Table 操作方法
 * 檢查 table 是否存在
 * 創建 table
 *
 * $wpdb->prepare
 * %s 會自動單引號
 * %1$s %2$d 不會有單引號
 *
 * TODO 移除空格 & 斷行
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
	 * 創建資料庫 table
	 *
	 * @param string $fields_sql 資料庫 table 欄位
	 * @return static|null
	 *
	 * @example
	 * $fields_sql = "
	 *  meta_id bigint(20) NOT NULL AUTO_INCREMENT,
	 *  post_id bigint(20) NOT NULL,
	 *  user_id bigint(20) NOT NULL,
	 *  meta_key varchar(255) DEFAULT NULL,
	 *  meta_value longtext,
	 *  PRIMARY KEY  (meta_id),
	 *  KEY post_id (post_id),
	 *  KEY user_id (user_id),
	 *  KEY meta_key (meta_key(191))
	 * ";
	 */
	public function create_table( string $fields_sql ): static|null {
		try {
			global $wpdb;
			$table_name      = "{$wpdb->prefix}{$this->table_name}";
			$is_table_exists = $this->is_exists();
			if ( $is_table_exists ) {
				return $this;
			}

			// 賦值到 $wpdb 屬性中
			$wpdb->{$this->table_name} = $table_name;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} ({$fields_sql}) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			\dbDelta($sql);
			return $this;
		} catch (\Throwable $th) {
			\J7\WpUtils\Classes\WC::logger("創建資料庫 table {$this->table_name} 失敗：{$th->getMessage()}", 'critical');
			return null;
		}
	}

	/**
	 * 判斷資料庫 table 是否存在
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
