<?php
/**
 * ApiRegisterTrait class
 * 註冊 API
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Traits;

if ( trait_exists( 'ApiRegisterTrait' ) ) {
	return;
}

/**
 * Class ApiRegisterTrait
 */
trait ApiRegisterTrait {

	/**
	 * Register APIs
	 * 預設的 permission_callback 是 '__return_true'，即不做任何權限檢查
	 *
	 * @param array     $apis api has following keys:
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 *
	 * @param ?string   $namespace Namespace.
	 * @param ?callable $default_permission_callback Default permission callback.
	 * @return void
	 */
	final protected function register_apis( array $apis, ?string $namespace = 'wp-utils/v1', ?callable $default_permission_callback = null ): void {

		foreach ( $apis as $api ) {
			// 用正則表達式替換 -, / 替換為 _
			$endpoint_fn = str_replace( '(?P<id>\d+)', 'with_id', $api['endpoint'] );
			$endpoint_fn = preg_replace( '/[-\/]/', '_', $endpoint_fn );

			if ( ! isset( $api['permission_callback'] ) ) {
				if ( is_callable( $default_permission_callback ) ) {
					$permission_callback = $default_permission_callback;
				} else {
					$permission_callback = '__return_true';
				}
			} else {
				$permission_callback = $api['permission_callback'];
			}

			\register_rest_route(
				$namespace,
				$api['endpoint'],
				array(
					'methods'             => $api['method'],
					'callback'            => array( $this, $api['method'] . '_' . $endpoint_fn . '_callback' ),
					'permission_callback' => $permission_callback,
				)
			);
		}
	}
}
