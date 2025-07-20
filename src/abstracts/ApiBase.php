<?php

namespace J7\WpAbstracts;

use WP_REST_Response;
use J7\WpUtils\Classes\WC;

/**
 * ApiBase class
 * 用法:
 * 1. 繼承 ApiBase 類別
 * 2. child class 指定 $apis 和 $namespace 就好
 * 3. 填寫 API schema
 *
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
 */
abstract class ApiBase {

	/** @var string $namespace */
	protected $namespace;

	/**
	 * @var array<array{
	 * endpoint:string,
	 * method:string,
	 * permission_callback?: callable|null,
	 * callback?: callable|null,
	 * schema?: array<string, mixed>|null
	 * }> $apis APIs
	 *
	 * @example
	 * $apis =[
	 *  [
	 *   'endpoint' => 'posts',
	 *   'method' => 'get',
	 *   'permission_callback' => null,
	 *   'callback' => null,
	 *   'schema' => null,
	 *  ]
	 * ]
	 * @phpstan-ignore-next-line
	 * */
	protected $apis = [];

	/** Constructor */
	protected function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_apis' ] );
	}

	/**
	 * 預設的 permission_callback 是 manage_options | manage_woocommerce
	 * 也可以按照需求複寫預設的 API 存取權限
	 *
	 * @return bool
	 */
	public function permission_callback(): bool {
		return \current_user_can( 'manage_options' ) || \current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Register APIs
	 * 預設的 permission_callback 是 '__return_true'，即不做任何權限檢查
	 *
	 * @return void
	 * @throws \Exception 如果 namespace 未設定，則拋出例外
	 */
	public function register_apis(): void {
		if ( ! $this->namespace ) {
			throw new \Exception( 'namespace is required' );
		}

		foreach ( $this->apis as $api ) {
			@[ // phpcs:ignore
			'endpoint'            => $endpoint,
			'method'              => $method,
			'permission_callback' => $permission_callback,
			'callback'            => $callback,
			'schema'              => $schema,
			] = $api;

			// 用正則表達式替換 -, / 替換為 _
			$formatted_fn_name = str_replace( '(?P<id>\d+)', 'with_id', $endpoint );
			$formatted_fn_name = preg_replace( '/[-\/]/', '_', $formatted_fn_name );

			/** @var callable $formatted_permission_callback 預設使用 permission_callback 方法 */
			$formatted_permission_callback = [ $this, 'permission_callback' ];
			if ( is_callable( $permission_callback ) ) {
				// 如果個別 API 有設定 permission_callback，則使用個別的
				$formatted_permission_callback = $permission_callback;
			}

			/** @var callable $callback 預設使用以下規則的 callback 名稱: [method]_[endpoint]_callback */
			$formatted_callback = [ $this, "{$method}_{$formatted_fn_name}_callback" ];
			if ( is_callable( $callback ) ) {
				// 如果個別 API 有設定 callback，則使用個別的
				$formatted_callback = $callback;
			}

			\register_rest_route(
			$this->namespace,
			$endpoint,
			[
				'methods'             => $method,
				'callback'            => function ( $request ) use ( $formatted_callback ) {
					return $this->try(  $formatted_callback, $request );
				},
				'permission_callback' => $formatted_permission_callback,
				'schema'              => $schema,
			]
			);
		}
	}

	/**
	 * 嘗試執行回調函數並返回響應
	 * 用這個 try 將整個 callback 用 try catch 包起來，如果 callback 拋出異常，則返回500錯誤響應
	 *
	 * @param callable         $callback 要執行的回調函數
	 * @param \WP_REST_Request $request  請求對象
	 * @return \WP_REST_Response 響應對象
	 * @throws \Exception 如果回調函數拋出異常，則捕獲異常並返回500錯誤響應
	 * @phpstan-ignore-next-line
	 */
	public function try( $callback, $request ) {
		try {
			return call_user_func( $callback, $request );
		} catch (\Exception $e) {
			$method = $request->get_method();
			$data   = match ( $method ) {
				'GET' => $request->get_query_params(),
				default => $request->get_json_params() ?: $request->get_body_params(),
			};

			// 如果開啟 DEBUG 模式就印出 log
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				WC::logger(
				"API 錯誤: {$e->getMessage()}",
				'critical',
				[
					'request_endpoint' => $request->get_route(),
					'request_method'   => $method,
					'request_params'   => $data,
				],
					);
			}

			return new WP_REST_Response(
				[
					'code'    => $e->getCode(),
					'message' => $e->getMessage(),
					'data'    => $data,
				],
				500
			);
		}
	}


	/**
	 * 檢查基本驗證 Basic Auth
	 * 當你需要一個使用 WP 帳號密碼的簡單的驗證機制時，可以使用 Basic Auth。
	 * 帳號: WP 帳號的 username
	 * 密碼: 需要到 wp-admin/user-edit.php?user_id=1 這樣的網址中設定`應用程式密碼`
	 * 拿到用戶後可以再 wp_set_current_user 設定當前用戶
	 *
	 * @return \WP_Error|\WP_User
	 */
	protected static function get_user_by_basic_auth(): \WP_Error|\WP_User {

		// 檢查PHP_AUTH_USER和PHP_AUTH_PW是否設置
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) || ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			return new \WP_Error( 'rest_forbidden', 'Authorization header missing', [ 'status' => 401 ] );
		}

		// 驗證使用者名稱和密碼
		$user = \wp_authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ); // phpcs:ignore

		return $user;
	}
}
