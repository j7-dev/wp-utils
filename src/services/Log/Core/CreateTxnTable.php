<?php

namespace J7\WpServices\Log\Core;

use J7\WpHelpers\Table;

/**
 * 創建 Transaction Txn Table
 * 需要有一張獨立的表，方便未來做排序
 * 這樣也能確保每位用戶都有針對不同點數類型的點數都有一個錢包
 * 紀錄
 * txn_id | wallet_id | title | type | modified_by | point_changed | new_balance | ref_id | ref_type | expire_date | created_at | updated_at
 */
abstract class CreateTxnTable {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** @var string 錢包 Table 名稱 */
	public string $table_name = 'j7_wallet_logs';

	/** Constructor */
	public function __construct() {
		$table = new Table($this->table_name);

		// -- 常用查詢組合的索引
		// KEY idx_wallet_created (wallet_id, created_at),        -- 查錢包交易記錄（時間排序）
		// KEY idx_wallet_type (wallet_id, type),                 -- 查錢包特定類型交易
		// KEY idx_expire_date (expire_date),                     -- 過期處理專用
		// KEY idx_ref (ref_type, ref_id),                        -- 反查關聯記錄
		// KEY idx_modified_created (modified_by, created_at)     -- 查操作者記錄

		$table->create_table(
			"
			txn_id bigint(20) NOT NULL AUTO_INCREMENT,
	 		wallet_id bigint(20) NOT NULL,
			title varchar(255) NOT NULL,
			type ENUM('deposit', 'withdraw', 'expire', 'bonus', 'refund', 'modify', 'cron', 'system') NOT NULL,
			modified_by bigint(20) NOT NULL,
	 		point_changed DECIMAL(15,4) NOT NULL,
			new_balance DECIMAL(15,4) NOT NULL,
			ref_id bigint(20) DEFAULT NULL,
			ref_type varchar(20) DEFAULT NULL,
			expire_date datetime NOT NULL DEFAULT '9999-12-31 23:59:59',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	 		PRIMARY KEY  (txn_id),
	 		KEY idx_wallet_created (wallet_id, created_at),
	 		KEY idx_wallet_type (wallet_id, type),
			KEY idx_expire_date (expire_date),
			KEY idx_ref (ref_type, ref_id),
			KEY idx_modified_created (modified_by, created_at)
			"
		);
	}
}
