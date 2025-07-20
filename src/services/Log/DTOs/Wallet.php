<?php

namespace J7\WpServices\Log\DTOs;

use J7\WpAbstracts\DTO;

/**
 * Wallet
 * 錢包 DTO，對應 j7_wallets 表
 * 每位用戶針對不同點數類型都有一個錢包
 */
class Wallet extends DTO {

	/** @var int 錢包 ID */
	public int $wallet_id;

	/** @var int 用戶 ID */
	public int $user_id;

	/** @var int 點數類型 ID */
	public int $point_type_id;

	/** @var PointType 點數類型物件 */
	public PointType $point_type;

	/** @var float 餘額 */
	public float $balance;

	/** @var string 創建時間 */
	public string $created_at;

	/** @var string 更新時間 */
	public string $updated_at;

	/**
	 * 從資料庫記錄創建錢包實例
	 *
	 * @param object $record 資料庫記錄
	 * @return self
	 */
	public static function create( object $record ): self {
		// 獲取點數類型
		$point_type_post = \get_post( $record->point_type_id );
		$point_type      = $point_type_post ? PointType::create( $point_type_post ) : null;

		$args = [
			'wallet_id'     => (int) $record->wallet_id,
			'user_id'       => (int) $record->user_id,
			'point_type_id' => (int) $record->point_type_id,
			'balance'       => (float) $record->balance,
			'created_at'    => $record->created_at,
			'updated_at'    => $record->updated_at,
		];

		if ( $point_type ) {
			$args['point_type'] = $point_type;
		}

		return new self( $args );
	}

	/**
	 * 取得用戶特定點數類型的錢包
	 *
	 * @param int $user_id 用戶 ID
	 * @param int $point_type_id 點數類型 ID
	 * @return self|null
	 */
	public static function get_user_wallet( int $user_id, int $point_type_id ): ?self {
		global $wpdb;
		$table_name = $wpdb->prefix . 'j7_wallets';

		$record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d AND point_type_id = %d",
				$user_id,
				$point_type_id
			)
		);

		return $record ? self::create( $record ) : null;
	}

	/**
	 * 取得用戶的所有錢包
	 *
	 * @param int $user_id 用戶 ID
	 * @return self[]
	 */
	public static function get_user_wallets( int $user_id ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'j7_wallets';

		$records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
				$user_id
			)
		);

		return array_map( [ self::class, 'create' ], $records );
	}

	/**
	 * 根據錢包 ID 取得錢包
	 *
	 * @param int $wallet_id 錢包 ID
	 * @return self|null
	 */
	public static function get_wallet_by_id( int $wallet_id ): ?self {
		global $wpdb;
		$table_name = $wpdb->prefix . 'j7_wallets';

		$record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE wallet_id = %d",
				$wallet_id
			)
		);

		return $record ? self::create( $record ) : null;
	}
}
