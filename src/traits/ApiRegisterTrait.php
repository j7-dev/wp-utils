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
	 * APIs
	 *
	 * @var array
	 * - endpoint: string
	 * - method: 'get' | 'post' | 'patch' | 'delete'
	 * - permission_callback : callable
	 */
	protected $apis = array();

	/**
	 * Register APIs
	 * 預設的 permission_callback 是 current_user_can( 'manage_options' )
	 *
	 * @param string   $namespace Namespace.
	 * @param callable $default_permission_callback Default permission callback.
	 * @return void
	 */
	final public function register_apis( string $namespace = 'wp-utils/v1', callable $default_permission_callback ): void {

		foreach ( $this->apis as $api ) {
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
