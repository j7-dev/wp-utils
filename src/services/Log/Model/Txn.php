<?php

namespace J7\WpServices\Log\Model;

use J7\WpAbstracts\DTO;

/**
 * Txn
 * 交易記錄 DTO，對應 j7_wallet_logs 表
 * 記錄用戶錢包的所有交易變動
 */
class Txn extends DTO {

	/** @var int 交易 ID */
	public int $txn_id;

	/** @var int 錢包 ID */
	public int $wallet_id;

	/** @var Wallet 錢包物件 */
	public Wallet $wallet;

	/** @var string 交易標題 */
	public string $title;

	/** @var string 交易類型 */
	public string $type;

	/** @var int 操作者 ID，0 代表系統 */
	public int $modified_by;

	/** @var float 點數變動量 */
	public float $point_changed;

	/** @var float 變動後餘額 */
	public float $new_balance;

	/** @var int|null 關聯 ID */
	public ?int $ref_id = null;

	/** @var string|null 關聯類型 */
	public ?string $ref_type = null;

	/** @var string 過期時間 */
	public string $expire_date;

	/** @var string 創建時間 */
	public string $created_at;

	/** @var string 更新時間 */
	public string $updated_at;

	/**
	 * 從資料庫記錄創建交易實例
	 *
	 * @param object $record 資料庫記錄
	 * @return self
	 */
	public static function create( object $record ): self {
		// 獲取錢包資訊
		$wallet = Wallet::get_wallet_by_id( (int) $record->wallet_id );

		$args = [
			'txn_id'        => (int) $record->txn_id,
			'wallet_id'     => (int) $record->wallet_id,
			'title'         => $record->title,
			'type'          => $record->type,
			'modified_by'   => (int) $record->modified_by,
			'point_changed' => (float) $record->point_changed,
			'new_balance'   => (float) $record->new_balance,
			'ref_id'        => $record->ref_id ? (int) $record->ref_id : null,
			'ref_type'      => $record->ref_type,
			'expire_date'   => $record->expire_date,
			'created_at'    => $record->created_at,
			'updated_at'    => $record->updated_at,
		];

		if ( $wallet ) {
			$args['wallet'] = $wallet;
		}

		return new self( $args );
	}

	/**
	 * 取得錢包的交易記錄
	 *
	 * @param int    $wallet_id 錢包 ID
	 * @param int    $limit 限制筆數
	 * @param int    $offset 偏移量
	 * @param string $order_by 排序欄位
	 * @param string $order 排序方向
	 * @return self[]
	 */
	public static function get_wallet_transactions(
		int $wallet_id,
		int $limit = 20,
		int $offset = 0,
		string $order_by = 'created_at',
		string $order = 'DESC'
	): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'j7_wallet_logs';

		$records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name}
				WHERE wallet_id = %d
				ORDER BY {$order_by} {$order}
				LIMIT %d OFFSET %d",
				$wallet_id,
				$limit,
				$offset
			)
		);

		return array_map( [ self::class, 'create' ], $records );
	}

	/**
	 * 取得用戶的所有交易記錄
	 *
	 * @param int    $user_id 用戶 ID
	 * @param int    $limit 限制筆數
	 * @param int    $offset 偏移量
	 * @param string $type 交易類型過濾
	 * @return self[]
	 */
	public static function get_user_transactions(
		int $user_id,
		int $limit = 20,
		int $offset = 0,
		?string $type = null
	): array {
		global $wpdb;
		$logs_table   = $wpdb->prefix . 'j7_wallet_logs';
		$wallet_table = $wpdb->prefix . 'j7_wallets';

		$type_condition = $type ? $wpdb->prepare( 'AND logs.type = %s', $type ) : '';

		$records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT logs.* FROM {$logs_table} logs
				INNER JOIN {$wallet_table} wallet ON logs.wallet_id = wallet.wallet_id
				WHERE wallet.user_id = %d {$type_condition}
				ORDER BY logs.created_at DESC
				LIMIT %d OFFSET %d",
				$user_id,
				$limit,
				$offset
			)
		);

		return array_map( [ self::class, 'create' ], $records );
	}

	/**
	 * 取得交易類型的有效值
	 *
	 * @return array<string>
	 */
	public static function get_valid_types(): array {
		return [
			'deposit',  // 存入
			'withdraw', // 提取
			'expire',   // 過期
			'bonus',    // 獎勵
			'refund',   // 退款
			'modify',   // 修改
			'cron',     // 定時任務
			'system',   // 系統操作
		];
	}

	/**
	 * 驗證交易類型是否有效
	 *
	 * @param string $type 交易類型
	 * @return bool
	 */
	public static function is_valid_type( string $type ): bool {
		return in_array( $type, self::get_valid_types(), true );
	}
}
