<?php
/**
 * Auth class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'Auth' ) ) {
	return;
}
/**
 * Auth class
 */
abstract class Auth {

	/**
	 * 檢查基本驗證 Basic Auth
	 * 當你需要一個使用 WP 帳號密碼的簡單的驗證機制時，可以使用 Basic Auth。
	 * 帳號: WP 帳號的 username
	 * 密碼: 需要到 wp-admin/user-edit.php?user_id=1 這樣的網址中設定`應用程式密碼`
	 *
	 * @example:
	 * \register_rest_route(
	 *       'API_DOMAIN',
	 *       'YOUR_ENDPOINT',
	 *       array(
	 *           'methods'             => 'GET',
	 *           'callback'            => [ $this, 'YOUR_ENDPOINT_CALLBACK' ],
	 *           'permission_callback' => [ \J7\WpUtils\Classes\Auth, 'check_basic_auth' ],
	 *       )
	 * );
	 * @see https://developer.wordpress.org/reference/functions/user_can/
	 * @param string $capability 權限名稱
	 * @param mixed  ...$args 額外的參數
	 *
	 * @return \WP_Error|bool
	 */
	public static function check_basic_auth( $capability = 'manage_options', ...$args ): \WP_Error|bool {
		// 驗證使用者名稱和密碼
		$user = self::get_user_by_basic_auth();
		if ( \is_wp_error( $user ) ) {
			return $user;
		}

		return \user_can( $user, $capability, ...$args );
	}

	/**
	 * 檢查基本驗證 Basic Auth
	 * 當你需要一個使用 WP 帳號密碼的簡單的驗證機制時，可以使用 Basic Auth。
	 * 帳號: WP 帳號的 username
	 * 密碼: 需要到 wp-admin/user-edit.php?user_id=1 這樣的網址中設定`應用程式密碼`
	 *
	 * @return \WP_Error|\WP_User
	 */
	public static function get_user_by_basic_auth(): \WP_Error|\WP_User {

		// 檢查PHP_AUTH_USER和PHP_AUTH_PW是否設置
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) || ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			return new \WP_Error( 'rest_forbidden', 'Authorization header missing', [ 'status' => 401 ] );
		}

		// 驗證使用者名稱和密碼
		$user = \wp_authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ); // phpcs:ignore

		return $user;
	}

	/**
	 * Check IP Permission
	 * 限制 API 只能由指定的 IP 範圍內訪問
	 *
	 * @example:
	 * \register_rest_route(
	 *       'v1/api',
	 *       'posts',
	 *       array(
	 *           'methods'             => 'GET',
	 *           'callback'            => array( $this, 'get_posts_callback' ),
	 *           'permission_callback' => array( $this, 'check_ip_permission' ),
	 *       )
	 * );
	 *
	 * @return bool
	 */
	public function check_ip_permission() {
		// 允許的 IP 範圍起始和結束 IP
		$start_ip = '61.220.100.0';
		$end_ip   = '61.220.100.10';

		// 將起始和結束 IP 轉換為長整型
		$start_ip_long = sprintf( '%u', ip2long( $start_ip ) );
		$end_ip_long   = sprintf( '%u', ip2long( $end_ip ) );

    // phpcs:disable
    $request_ip_long = sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
    // phpcs:enable

		// 檢查發起請求的 IP 是否在允許的範圍內
		return ( $request_ip_long >= $start_ip_long && $request_ip_long <= $end_ip_long );
	}
}
