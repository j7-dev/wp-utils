<?php

namespace J7\WpServices\Log\Core;

use J7\WpHelpers\Table;

/**
 * 創建錢包 Table
 * 需要有一張獨立的表，方便未來做排序
 * 這樣也能確保每位用戶都有針對不同點數類型的點數都有一個錢包
 * 紀錄
 * wallet_id | user_id | point_type_id | balance | created_at | updated_at
 */
abstract class CreateWalletTable {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** @var string 錢包 Table 名稱 */
	public string $table_name = 'j7_wallets';

	/** Constructor */
	public function __construct() {
		$table = new Table($this->table_name);
		$table->create_table(
			'
	 		wallet_id bigint(20) NOT NULL AUTO_INCREMENT,
	 		user_id bigint(20) NOT NULL,
	 		point_type_id bigint(20) NOT NULL,
	 		balance DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	 		PRIMARY KEY  (wallet_id),
	 		KEY user_id (user_id),
	 		KEY point_type_id (point_type_id)
			'
		);
	}
}
