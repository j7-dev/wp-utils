<?php
/**
 * WC Product CRUD 相關 utils
 */

namespace J7\WpUtils\Classes\WC;

if ( class_exists( 'Product' ) ) {
	return;
}

/**
 * Product class
 * Product CRUD 相關，包含批量創建、更新、刪除
 */
abstract class Product {

	/**
	 * 批量創建商品
	 *
	 * @param int    $qty 商品數量
	 * @param string $product_type 商品類型
	 * @return array{code: string, message: string, data: array{success_ids: array<int>, not_found_ids: array<int>}} 更新結果陣列
	 * @throws \Exception 當商品類型無效時，拋出例外
	 */
	public static function multi_create( int $qty, ?string $product_type = 'simple' ): array {
		\do_action( 'wp_utils_before_products_created', $qty, $product_type );

		$success_ids = [];
		for ($i = 0; $i < $qty; $i++) {
			$product = match ($product_type) {
				'simple' => new \WC_Product_Simple(),
				'variable' => new \WC_Product_Variable(),
				'grouped' => new \WC_Product_Grouped(),
				'external' => new \WC_Product_External(),
				default => throw new \Exception( 'Invalid product type' ),
			};
			$id = $product->get_id();
			$product->set_name( "新商品 #{$id}" );
			$product->save();
			$success_ids[] = $id;
		}

		$product_ids = $success_ids;
		\do_action( 'wp_utils_after_products_created', $product_ids, $qty, $product_type);

		return [
			'code'    => 'success',
			'message' => '創建成功',
			'data'    => [
				'success_ids'   => $success_ids,
				'not_found_ids' => [],
			],
		];
	}

	/**
	 * 批量更新商品
	 *
	 * @param array<int>            $ids 商品 ID 陣列
	 * @param ?array<string, mixed> $data 商品資料陣列
	 * @param ?array<string, mixed> $meta_data 商品 meta 資料陣列
	 * @return array{code: string, message: string, data: array{success_ids: array<int>, not_found_ids: array<int>}} 更新結果陣列
	 */
	public static function multi_update( array $ids, ?array $data = [], ?array $meta_data = [] ): array {
		\do_action( 'wp_utils_before_products_updated', $ids, $data, $meta_data );
		$not_found_ids = [];
		$success_ids   = [];
		foreach ($ids as $id) {
			$product = \wc_get_product( $id );
			if ( ! $product ) {
				$not_found_ids[] = $id;
				continue;
			}

			foreach ( $data as $key => $value ) {
				$method_name = 'set_' . $key;
				$product->$method_name( $value );
			}
			$product->save();

			foreach ( $meta_data as $key => $value ) {
				$product->update_meta_data( $key, $value );
			}

			$product->save_meta_data();
			$success_ids[] = $id;
		}

		$all_product_ids     = $ids;
		$updated_product_ids = $success_ids;
		\do_action( 'wp_utils_after_products_updated', $updated_product_ids, $all_product_ids, $data, $meta_data );

		return [
			'code'    => 'success',
			'message' => '更新成功',
			'data'    => [
				'success_ids'   => $success_ids,
				'not_found_ids' => $not_found_ids,
			],
		];
	}

	/**
	 * 批量刪除商品
	 *
	 * @param array<int> $ids 商品 ID 陣列
	 * @param bool       $force_delete 是否強制刪除
	 * @return array{code: string, message: string, data: array{success_ids: array<int>, not_found_ids: array<int>}} 更新結果陣列
	 */
	public static function multi_delete( array $ids, $force_delete = false ): array {
		\do_action( 'wp_utils_before_products_deleted', $ids, $force_delete );
		$not_found_ids = [];
		$success_ids   = [];
		foreach ($ids as $id) {
			$product = \wc_get_product( $id );
			if ( ! $product ) {
				$not_found_ids[] = $id;
				continue;
			}

			$product->delete( $force_delete );
			$success_ids[] = $id;
		}

		$all_product_ids     = $ids;
		$deleted_product_ids = $success_ids;
		\do_action( 'wp_utils_after_products_deleted', $deleted_product_ids, $all_product_ids, $force_delete );

		return [
			'code'    => 'success',
			'message' => '刪除成功',
			'data'    => [
				'success_ids'   => $success_ids,
				'not_found_ids' => $not_found_ids,
			],
		];
	}

	/**
	 * 更新 meta array
	 *
	 * @param int           $id 商品 ID
	 * @param string        $meta_key meta key
	 * @param array<string> $meta_values meta value array
	 * @return bool|\WP_Error
	 */
	public static function update_meta_array( int $id, string $meta_key, array $meta_values ): bool|\WP_Error {
		$product = \wc_get_product( $id );
		if ( ! $product ) {
			return new \WP_Error( 'product_not_found', '商品不存在', [ 'status' => 404 ] );
		}

		// 先刪除原本的 meta data
		$product->delete_meta_data( $meta_key );
		foreach ( $meta_values as $meta_value ) {
			$product->add_meta_data( $meta_key, $meta_value, false );
		}
		$product->save_meta_data();
		return true;
	}


	/**
	 * 添加 meta array
	 *
	 * @param int           $id 商品 ID
	 * @param string        $meta_key meta key
	 * @param array<string> $meta_values meta value array
	 * @return bool|\WP_Error
	 */
	public static function add_meta_array( int $id, string $meta_key, array $meta_values ): bool|\WP_Error {
		$product = \wc_get_product( $id );
		if ( ! $product ) {
			return new \WP_Error( 'product_not_found', '商品不存在', [ 'status' => 404 ] );
		}

		$origin_meta_values = \get_post_meta( $id, $meta_key, false );

		foreach ( $meta_values as $meta_value ) {
			// 檢查 $origin_meta_values 是否已經包含 $meta_value
			if (\in_array($meta_value, $origin_meta_values)) {
				continue;
			}

			$product->add_meta_data( $meta_key, $meta_value, false );
		}
		$product->save_meta_data();
		return true;
	}
}
