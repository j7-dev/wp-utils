<?php

namespace J7\WpServices\Log\CRUDs;

use J7\WpAbstracts\BaseCRUD;
use J7\WpAbstracts\DTO;
use J7\WpServices\Log\DTOs\Wallet;

/**
 * WalletCRUD
 * 錢包的 CRUD 操作類
 */
class WalletCRUD extends BaseCRUD {

	/**
	 * 取得基礎資料表名稱（不含前綴）
	 *
	 * @return string 基礎資料表名稱
	 */
	protected function getBaseTableName(): string {
		return 'j7_wallets';
	}

	/**
	 * 取得主鍵欄位名稱
	 *
	 * @return string 主鍵欄位名稱
	 */
	public function getPrimaryKey(): string {
		return 'wallet_id';
	}

	/**
	 * 取得對應的 DTO 類別名稱
	 *
	 * @return string DTO 類別名稱
	 */
	public function getDTOClass(): string {
		return Wallet::class;
	}

	/**
	 * 取得可填充的欄位清單
	 *
	 * @return array<string> 可填充的欄位名稱陣列
	 */
	public function getFillable(): array {
		return [
			'user_id',
			'point_type_id',
			'balance',
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
		return Wallet::create( $record );
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
		$required_fields = [ 'user_id', 'point_type_id' ];

		foreach ( $required_fields as $field ) {
			if ( null === $id && ! isset( $data[ $field ] ) ) {
				$this->logError( "缺少必要欄位: {$field}" );
				return false;
			}
		}

		// 驗證數值欄位
		if ( isset( $data['user_id'] ) && ! is_numeric( $data['user_id'] ) ) {
			$this->logError( '用戶 ID 必須是數字' );
			return false;
		}

		if ( isset( $data['point_type_id'] ) && ! is_numeric( $data['point_type_id'] ) ) {
			$this->logError( '點數類型 ID 必須是數字' );
			return false;
		}

		if ( isset( $data['balance'] ) && ! is_numeric( $data['balance'] ) ) {
			$this->logError( '餘額必須是數字' );
			return false;
		}

		// 驗證餘額不能為負數
		if ( isset( $data['balance'] ) && (float) $data['balance'] < 0 ) {
			$this->logError( '餘額不能為負數' );
			return false;
		}

		// 驗證用戶和點數類型的組合是否唯一（創建時）
		if ( null === $id && isset( $data['user_id'], $data['point_type_id'] ) ) {
			$existing = $this->findByUserAndPointType( (int) $data['user_id'], (int) $data['point_type_id'] );
			if ( $existing ) {
				$this->logError( '該用戶已存在此點數類型的錢包' );
				return false;
			}
		}

		return true;
	}

	/**
	 * 根據用戶 ID 查找錢包
	 *
	 * @param int $user_id 用戶 ID
	 * @return Wallet[] 錢包陣列
	 */
	public function findByUserId( int $user_id ): array {
		return $this->findBy( [ 'user_id' => $user_id ] );
	}

	/**
	 * 根據點數類型 ID 查找錢包
	 *
	 * @param int $point_type_id 點數類型 ID
	 * @return Wallet[] 錢包陣列
	 */
	public function findByPointTypeId( int $point_type_id ): array {
		return $this->findBy( [ 'point_type_id' => $point_type_id ] );
	}

	/**
	 * 根據用戶 ID 和點數類型 ID 查找錢包
	 *
	 * @param int $user_id 用戶 ID
	 * @param int $point_type_id 點數類型 ID
	 * @return Wallet|null 錢包或 null
	 */
	public function findByUserAndPointType( int $user_id, int $point_type_id ): ?Wallet {
		$records = $this->findBy(
			[
				'user_id'       => $user_id,
				'point_type_id' => $point_type_id,
			]
			);

		return ! empty( $records ) ? $records[0] : null;
	}

	/**
	 * 創建或取得錢包
	 * 如果不存在則創建，存在則返回現有的
	 *
	 * @param int   $user_id 用戶 ID
	 * @param int   $point_type_id 點數類型 ID
	 * @param float $initial_balance 初始餘額
	 * @return Wallet|false 錢包實例或 false
	 */
	public function findOrCreate( int $user_id, int $point_type_id, float $initial_balance = 0.0 ): Wallet|false {
		// 先嘗試查找現有錢包
		$existing = $this->findByUserAndPointType( $user_id, $point_type_id );

		if ( $existing ) {
			return $existing;
		}

		// 創建新錢包
		$result = $this->create(
			[
				'user_id'       => $user_id,
				'point_type_id' => $point_type_id,
				'balance'       => $initial_balance,
			]
			);

		return $result instanceof Wallet ? $result : false;
	}

	/**
	 * 更新錢包餘額
	 *
	 * @param int   $wallet_id 錢包 ID
	 * @param float $amount 變動金額（正數為增加，負數為減少）
	 * @return bool 是否成功
	 */
	public function updateBalance( int $wallet_id, float $amount ): bool {
		// 取得當前錢包
		$wallet = $this->find( $wallet_id );

		if ( ! $wallet ) {
			$this->logError( "錢包 ID {$wallet_id} 不存在" );
			return false;
		}

		$new_balance = $wallet->balance + $amount;

		// 檢查餘額不能為負數
		if ( $new_balance < 0 ) {
			$this->logError( '餘額不足' );
			return false;
		}

		// 更新餘額
		return $this->update( $wallet_id, [ 'balance' => $new_balance ] );
	}

	/**
	 * 設定錢包餘額
	 *
	 * @param int   $wallet_id 錢包 ID
	 * @param float $balance 新餘額
	 * @return bool 是否成功
	 */
	public function setBalance( int $wallet_id, float $balance ): bool {
		if ( $balance < 0 ) {
			$this->logError( '餘額不能為負數' );
			return false;
		}

		return $this->update( $wallet_id, [ 'balance' => $balance ] );
	}

	/**
	 * 取得用戶的總餘額（所有錢包）
	 *
	 * @param int $user_id 用戶 ID
	 * @return float 總餘額
	 */
	public function getUserTotalBalance( int $user_id ): float {
		$sql = $this->wpdb->prepare(
			"SELECT SUM(balance) FROM {$this->getTableName()} WHERE user_id = %d",
			$user_id
		);

		$result = $this->wpdb->get_var( $sql );

		return $result ? (float) $result : 0.0;
	}

	/**
	 * 取得餘額最高的錢包
	 *
	 * @param int $limit 限制數量
	 * @return Wallet[] 錢包陣列
	 */
	public function getTopBalanceWallets( int $limit = 10 ): array {
		return $this->orderBy( 'balance', 'DESC' )
					->limit( $limit )
					->get();
	}

	/**
	 * 批量更新錢包餘額
	 *
	 * @param array<int, float> $updates 錢包 ID => 變動金額的映射
	 * @return bool 是否成功
	 */
	public function batchUpdateBalance( array $updates ): bool {
		$this->wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $updates as $wallet_id => $amount ) {
				$result = $this->updateBalance( (int) $wallet_id, (float) $amount );
				if ( ! $result ) {
					throw new \Exception( "更新錢包 {$wallet_id} 餘額失敗" );
				}
			}

			$this->wpdb->query( 'COMMIT' );
			return true;

		} catch ( \Exception $e ) {
			$this->wpdb->query( 'ROLLBACK' );
			$this->logError( '批量更新錢包餘額失敗', $e->getMessage() );
			return false;
		}
	}
}
