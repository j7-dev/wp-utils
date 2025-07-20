<?php

namespace J7\WpServices\Log\CRUDs;

use J7\WpAbstracts\BaseCRUD;
use J7\WpAbstracts\DTO;
use J7\WpServices\Log\Model\Txn;
use J7\WpServices\Log\Model\Enums\TxnType;

/**
 * TxnCRUD
 * 交易記錄的 CRUD 操作類
 */
class TxnCRUD extends BaseCRUD {

	/**
	 * 取得基礎資料表名稱（不含前綴）
	 *
	 * @return string 基礎資料表名稱
	 */
	protected function getBaseTableName(): string {
		return 'j7_wallet_logs';
	}

	/**
	 * 取得主鍵欄位名稱
	 *
	 * @return string 主鍵欄位名稱
	 */
	public function getPrimaryKey(): string {
		return 'txn_id';
	}

	/**
	 * 取得對應的 DTO 類別名稱
	 *
	 * @return string DTO 類別名稱
	 */
	public function getDTOClass(): string {
		return Txn::class;
	}

	/**
	 * 取得可填充的欄位清單
	 *
	 * @return array<string> 可填充的欄位名稱陣列
	 */
	public function getFillable(): array {
		return [
			'wallet_id',
			'title',
			'type',
			'modified_by',
			'point_changed',
			'new_balance',
			'ref_id',
			'ref_type',
			'expire_date',
			'created_at',
			'updated_at',
		];
	}

	/**
	 * 創建 DTO 實例
	 *
	 * @param object $record 資料庫記錄
	 * @return DTO DTO 實例
	 */
	protected function createDTO( object $record ): DTO {
		return Txn::create( $record );
	}

	/**
	 * 驗證資料
	 *
	 * @param array<string, mixed> $data 要驗證的資料
	 * @param int|null             $id 更新時的記錄 ID
	 * @return bool 驗證是否通過
	 */
	protected function validateData( array $data, ?int $id = null ): bool {
		// 驗證必要欄位
		$required_fields = [ 'wallet_id', 'title', 'type', 'point_changed', 'new_balance' ];

		foreach ( $required_fields as $field ) {
			if ( null === $id && ! isset( $data[ $field ] ) ) {
				$this->logError( "缺少必要欄位: {$field}" );
				return false;
			}
		}

		// 驗證交易類型
		if ( isset( $data['type'] ) && ! in_array( $data['type'], TxnType::values(), true ) ) {
			$this->logError( "無效的交易類型: {$data['type']}" );
			return false;
		}

		// 驗證數值欄位
		if ( isset( $data['wallet_id'] ) && ! is_numeric( $data['wallet_id'] ) ) {
			$this->logError( '錢包 ID 必須是數字' );
			return false;
		}

		if ( isset( $data['point_changed'] ) && ! is_numeric( $data['point_changed'] ) ) {
			$this->logError( '點數變動量必須是數字' );
			return false;
		}

		if ( isset( $data['new_balance'] ) && ! is_numeric( $data['new_balance'] ) ) {
			$this->logError( '新餘額必須是數字' );
			return false;
		}

		return true;
	}

	/**
	 * 根據錢包 ID 查找交易記錄
	 *
	 * @param int $wallet_id 錢包 ID
	 * @return Txn[] 交易記錄陣列
	 */
	public function findByWalletId( int $wallet_id ): array {
		return $this->findBy( [ 'wallet_id' => $wallet_id ] );
	}

	/**
	 * 根據交易類型查找交易記錄
	 *
	 * @param string $type 交易類型
	 * @return Txn[] 交易記錄陣列
	 */
	public function findByType( string $type ): array {
		return $this->findBy( [ 'type' => $type ] );
	}

	/**
	 * 根據用戶 ID 查找交易記錄（需要 JOIN 錢包表）
	 *
	 * @param int $user_id 用戶 ID
	 * @return Txn[] 交易記錄陣列
	 */
	public function findByUserId( int $user_id ): array {
		$wallet_table = $this->wpdb->prefix . 'j7_wallets';
		$txn_table    = $this->getTableName();

		$sql = $this->wpdb->prepare(
			"SELECT t.* FROM {$txn_table} t 
			 INNER JOIN {$wallet_table} w ON t.wallet_id = w.wallet_id 
			 WHERE w.user_id = %d 
			 ORDER BY t.created_at DESC",
			$user_id
		);

		$records = $this->wpdb->get_results( $sql );

		return array_map( [ $this, 'createDTO' ], $records );
	}

	/**
	 * 取得用戶的交易統計
	 *
	 * @param int    $user_id 用戶 ID
	 * @param string $type 交易類型（可選）
	 * @return array<string, mixed> 統計資料
	 */
	public function getUserTransactionStats( int $user_id, string $type = '' ): array {
		$wallet_table = $this->wpdb->prefix . 'j7_wallets';
		$txn_table    = $this->getTableName();

		$where_type = '';
		if ( ! empty( $type ) ) {
			$where_type = $this->wpdb->prepare( ' AND t.type = %s', $type );
		}

		$sql = $this->wpdb->prepare(
			"SELECT 
				COUNT(*) as total_count,
				SUM(t.point_changed) as total_amount,
				AVG(t.point_changed) as avg_amount,
				MAX(t.point_changed) as max_amount,
				MIN(t.point_changed) as min_amount
			 FROM {$txn_table} t 
			 INNER JOIN {$wallet_table} w ON t.wallet_id = w.wallet_id 
			 WHERE w.user_id = %d{$where_type}",
			$user_id
		);

		$result = $this->wpdb->get_row( $sql, ARRAY_A );

		return [
			'total_count'  => (int) $result['total_count'],
			'total_amount' => (float) $result['total_amount'],
			'avg_amount'   => (float) $result['avg_amount'],
			'max_amount'   => (float) $result['max_amount'],
			'min_amount'   => (float) $result['min_amount'],
		];
	}

	/**
	 * 批量創建交易記錄
	 *
	 * @param array<array<string, mixed>> $transactions 交易記錄陣列
	 * @return bool 是否成功
	 */
	public function batchCreate( array $transactions ): bool {
		$this->wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $transactions as $transaction ) {
				$result = $this->create( $transaction );
				if ( false === $result ) {
					throw new \Exception( '批量創建交易記錄失敗' );
				}
			}

			$this->wpdb->query( 'COMMIT' );
			return true;

		} catch ( \Exception $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			$this->logError( '批量創建交易記錄失敗', $e->getMessage() );
			return false;
		}
	}
}
