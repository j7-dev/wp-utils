<?php

namespace J7\WpServices\Log\DTOs\Enums;

/**
 * TxnType Enum
 * 交易類型枚舉
 */
enum TxnType: string {
	case DEPOSIT  = 'deposit';  // 存入
	case WITHDRAW = 'withdraw'; // 提取
	case EXPIRE   = 'expire';   // 過期
	case BONUS    = 'bonus';    // 獎勵
	case REFUND   = 'refund';   // 退款
	case MODIFY   = 'modify';   // 修改
	case CRON     = 'cron';     // 定時任務
	case SYSTEM   = 'system';   // 系統操作

	/**
	 * 取得所有交易類型的值
	 *
	 * @return array<string>
	 */
	public static function values(): array {
		return array_column( self::cases(), 'value' );
	}

	/**
	 * 取得交易類型的中文說明
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::DEPOSIT  => '存入',
			self::WITHDRAW => '提取',
			self::EXPIRE   => '過期',
			self::BONUS    => '獎勵',
			self::REFUND   => '退款',
			self::MODIFY   => '修改',
			self::CRON     => '定時任務',
			self::SYSTEM   => '系統操作',
		};
	}
}
