<?php

namespace J7\WpServices\Log;

use J7\WpServices\Log\CRUDs\TxnCRUD;
use J7\WpServices\Log\CRUDs\WalletCRUD;
use J7\WpAbstracts\BaseCRUD;

/**
 * CRUD Factory
 * 用於簡化 CRUD 實例的創建
 */
class CRUDFactory {

	/** @var array<string, BaseCRUD> CRUD 實例緩存 */
	private static array $instances = [];

	/**
	 * 取得交易記錄 CRUD 實例
	 *
	 * @return TxnCRUD 交易記錄 CRUD 實例
	 */
	public static function txn(): TxnCRUD {
		if ( ! isset( self::$instances['txn'] ) ) {
			self::$instances['txn'] = new TxnCRUD();
		}

		return self::$instances['txn'];
	}

	/**
	 * 取得錢包 CRUD 實例
	 *
	 * @return WalletCRUD 錢包 CRUD 實例
	 */
	public static function wallet(): WalletCRUD {
		if ( ! isset( self::$instances['wallet'] ) ) {
			self::$instances['wallet'] = new WalletCRUD();
		}

		return self::$instances['wallet'];
	}

	/**
	 * 根據類型取得 CRUD 實例
	 *
	 * @param string $type CRUD 類型 ('txn' 或 'wallet')
	 * @return BaseCRUD CRUD 實例
	 * @throws \InvalidArgumentException 當類型不支援時
	 */
	public static function make( string $type ): BaseCRUD {
		return match ( $type ) {
			'txn'    => self::txn(),
			'wallet' => self::wallet(),
			default  => throw new \InvalidArgumentException( "不支援的 CRUD 類型: {$type}" ),
		};
	}

	/**
	 * 清除所有 CRUD 實例緩存
	 *
	 * @return void
	 */
	public static function clearCache(): void {
		self::$instances = [];
	}

	/**
	 * 取得已緩存的 CRUD 實例列表
	 *
	 * @return array<string> 已緩存的類型列表
	 */
	public static function getCachedTypes(): array {
		return array_keys( self::$instances );
	}
}
