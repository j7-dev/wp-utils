<?php
/**
 * ApiBase class
 * 用法:
 * 1. 繼承 ApiBase 類別
 * 2. parent class 指定 $apis 和 $namespace 就好
 */

namespace J7\WpUtils\Classes;

use WP_REST_Response;

if ( class_exists( 'ApiBase' ) ) {
	return;
}

/**
 * Class ApiBase
 */
abstract class ApiBase {

	/** @var string $namespace */
	protected $namespace;

	/** @var array{endpoint:string,method:string,permission_callback: callable|null }[] APIs */
	protected $apis = [
		// [
		// 'endpoint'            => 'posts',
		// 'method'              => 'get',
		// 'permission_callback' => null,
		// ],
	];

	/** Constructor */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_apis' ] );
	}

	/**
	 * 預設的 permission_callback 是 manage_woocommerce
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
			// 用正則表達式替換 -, / 替換為 _
			$endpoint_fn = str_replace( '(?P<id>\d+)', 'with_id', $api['endpoint'] );
			$endpoint_fn = preg_replace( '/[-\/]/', '_', $endpoint_fn );

			if ( ! isset( $api['permission_callback'] ) ) {
				$permission_callback = [ $this, 'permission_callback' ];
			} else {
				$permission_callback = $api['permission_callback'];
			}

			/** @var callable $callback */
			$callback = [ $this, $api['method'] . '_' . $endpoint_fn . '_callback' ];

			\register_rest_route(
				$this->namespace,
				$api['endpoint'],
				[
					'methods'             => $api['method'],
					'callback'            => function ( $request ) use ( $callback ) {
						return $this->try(  $callback, $request );
					},
					'permission_callback' => $permission_callback,
				]
			);
		}
	}

	/**
	 * 嘗試執行回調函數並返回響應
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
}
