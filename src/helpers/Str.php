<?php

declare( strict_types = 1 );

namespace J7\WpHelpers;

/**
 * String 操作方法
 */
class Str {

	/**
	 * 建構子
	 *
	 * @param string $value 字串
	 */
	public function __construct(
		/** @var string $value 字串 */
		public string $value,
	) {
	}

	/**  @return bool 驗證字串是否為非 urlencode 過的純 ASCII 字串 */
	public function is_english(): bool {
		return \preg_match('/^[a-zA-Z0-9 ]+$/', $this->value) === 1;
	}

	/**  @return bool 驗證字串是否為非 urlencode 過的純 ASCII 字串 */
	public function is_ascii(): bool {
		if ( $this->contains_non_ascii() || $this->is_urlencoded() ) {
			return false; // 包含非ASCII字符或已被 urlencode
		}

		return true; // 純英文字串
	}

	/**  @return bool 檢查字串是否包含中文字元等非 ASCII 字元 */
	public function contains_non_ascii(): bool {
		return \preg_match( '/[^\x00-\x7F]/', $this->value ) !== 0;
	}

	/** @return bool 檢查字串是否已被 urlencode */
	public function is_urlencoded(): bool {
		$decoded = urldecode( $this->value );
		return $decoded !== $this->value;
	}

	/**
	 * 生成隨機字串
	 *
	 * @param int    $length 字串長度
	 * @param string $keyspace 字元集
	 * @param string $extend 額外字元
	 *
	 * @return string
	 * @throws \RangeException RangeException.
	 */
	public static function random( int $length = 64, ?string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ?string $extend = '' ): string {
		if ( $length < 1 ) {
			throw new \RangeException( '長度必須是正整數' );
		}
		$keyspace .= $extend; // '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';

		$pieces = [];
		$max    = mb_strlen( $keyspace, '8bit' ) - 1;
		for ( $i = 0; $i < $length; ++$i ) {
			$pieces [] = $keyspace[ random_int( 0, $max ) ];
		}
		return implode( '', $pieces );
	}
}
