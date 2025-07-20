<?php

declare( strict_types = 1 );

namespace J7\WpUtils;

/**
 * IP 工具
 */
abstract class IP {
	/**
	 * 檢查 IP 是否在指定範圍內
	 *
	 * @param string $from_ip 起始 IP
	 * @param string $to_ip 結束 IP
	 * @param string $target_ip 要檢查的 IP
	 * @return bool
	 */
	public static function in_range( string $from_ip = '', string $to_ip = '', string $target_ip = '' ): bool {
		if ( !$from_ip && !$to_ip ) {
			return false;
		}

		// 將起始和結束 IP 轉換為長整型
		$from_ip_long = sprintf( '%u', ip2long( $from_ip ) );
		$to_ip_long   = sprintf( '%u', ip2long( $to_ip ) );

    // phpcs:disable
    $request_ip_long = sprintf("%u", ip2long($target_ip ?: $_SERVER['REMOTE_ADDR']));
    // phpcs:enable

		// 檢查發起請求的 IP 是否在允許的範圍內
		if ( $from_ip_long && $to_ip_long ) {
			return ( $request_ip_long >= $from_ip_long && $request_ip_long <= $to_ip_long );
		}

		if ( $from_ip_long ) {
			return ( $request_ip_long >= $from_ip_long );
		}

		if ( $to_ip_long ) {
			return ( $request_ip_long <= $to_ip_long );
		}

		return false;
	}

	/**
	 * 針對台灣地區網路環境優化的 IP 獲取函數
	 * 適用於: 中華電信/遠傳/台灣大哥大等 ISP 的光纖/4G/5G 網路
	 *
	 * @return string|null
	 */
	public static function get_client_ip(): string|null {
		$ip_headers = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ($ip_headers as $header) {
			if (!empty($_SERVER[ $header ])) {
				$ip = $_SERVER[ $header ]; // phpcs:ignore
				if (strpos($ip, ',') !== false) {
					$ips = explode(',', $ip);
					$ip  = trim($ips[0]);
				}
				if (filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}

		return null;
	}
}
