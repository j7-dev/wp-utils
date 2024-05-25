<?php
/**
 * Auth class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils;

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
	 *       'v1/api',
	 *       'posts',
	 *       array(
	 *           'methods'             => 'GET',
	 *           'callback'            => array( $this, 'get_template_sites_by_user_id_callback' ),
	 *           'permission_callback' => array( $this, 'check_basic_auth' ),
	 *       )
	 * );
	 *
	 * @param \WP_REST_Request $request - the request object.
	 * @return \WP_Error|bool
	 */
	public static function check_basic_auth( $request ) {
		// 檢查PHP_AUTH_USER和PHP_AUTH_PW是否設置
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) || ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			return new \WP_Error( 'rest_forbidden', 'Authorization header missing', array( 'status' => 401 ) );
		}

		// 驗證使用者名稱和密碼
		$user = \wp_authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ); // phpcs:ignore
		if ( \is_wp_error( $user ) ) {
			return new \WP_Error( 'rest_forbidden', 'Invalid username or password', array( 'status' => 401 ) );
		}

		return true;
	}
}
