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

	/** @var Enums\TxnType::value 交易類型 */
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
	 * 驗證交易類型
	 *
	 * @throws \Exception 如果交易類型無效
	 */
	protected function validate(): void {
		Enums\TxnType::from( $this->type );
	}
}
