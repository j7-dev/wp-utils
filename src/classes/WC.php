<?php
/**
 * WC class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if (class_exists('WC')) {
	return;
}

/**
 * WC class
 */
abstract class WC {


	/**
	 * Is HPOS enabled
	 *
	 * @return bool
	 */
	public static function is_hpos_enabled(): bool {
		return class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Get Top Sales Products
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function get_top_sales_products( $limit = 10 ) {
		global $wpdb;
		// 执行查询
		$top_selling_products = $wpdb->get_results(
			$wpdb->prepare(
				"
    SELECT post_id, CAST(meta_value AS UNSIGNED) AS total_sales
    FROM {$wpdb->postmeta}
    WHERE meta_key = 'total_sales'
    ORDER BY total_sales DESC
    LIMIT %d
",
				$limit
			)
		);

		$formatted_top_selling_products = array_map(
			function ( $product ) {
				$product_id   = $product->post_id;
				$product_name = \get_the_title($product_id);
				$total_sales  = $product->total_sales;

				return [
					'id'          => (string) $product_id,
					'name'        => $product_name,
					'total_sales' => (float) $total_sales,
				];
			},
			$top_selling_products
		);

		return $formatted_top_selling_products;
	}


	/**
	 * Get formatted meta data
	 * 原本是 WC_Meta_Data[]，轉換成 key => value 的 array
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	public static function get_formatted_meta_data( \WC_Product $product ): array {
		$meta_data           = $product->get_meta_data();
		$formatted_meta_data = [];
		foreach ($meta_data as $meta) {
			$formatted_meta_data[ $meta->key ] = $meta->value;
		}

		return $formatted_meta_data;
	}

	/**
	 * 用產品反查訂單
	 * TODO 支援 HPOS
	 *
	 * @param int        $product_id 產品 ID
	 * @param array|null $args 參數
	 * - user_id int 使用者 ID，預設 current_user_id
	 * - limit int 查詢筆數，預設 10
	 * - status string[]|string 訂單狀態 'any' | 'wc-completed' | 'wc-processing' | 'wc-on-hold' | 'wc-pending' | 'wc-cancelled' | 'wc-refunded' | 'wc-failed' , 預設 [ 'wc-completed', 'wc-processing' ]
	 *
	 * @return array string[] order_ids
	 */
	public static function get_order_ids_by_product_id( int $product_id, ?array $args ): array {
		global $wpdb;
		$user_id  = $args['user_id'] ?? \get_current_user_id();
		$limit    = $args['limit'] ?? 10;
		$statuses = $args['status'] ?? 'any';
		if ( is_array( $statuses ) ) {
			$statuses_string  = implode(
				',',
				array_map(
					function ( $status ) {
						return '"' . $status . '"';
					},
					$statuses
				)
			);
			$status_condition = sprintf(
				'AND posts.post_status IN ( %1$s )',
				$statuses_string
			);
		} else {
			$status_condition = ( $statuses === 'any' ) ? '' : sprintf(
				'AND posts.post_status = %1$s',
				$statuses
			);

		}

		try {
			$prepare = $wpdb->prepare(
				"
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_author = %1\$s
          %2\$s
          AND order_items.order_item_type = 'line_item'
          AND order_item_meta.meta_key = '_product_id'
          AND order_item_meta.meta_value = %3\$s
        ORDER BY order_items.order_id DESC
        LIMIT %4\$s",
				$user_id,
				$status_condition,
				$product_id,
				$limit
			);

			return $wpdb->get_col( str_replace( '\"', '"', $prepare ) );
		} catch ( \Exception $e ) {
			\J7\WpUtils\Classes\Log::info( $e->getMessage() );

			return [];
		}
	}

	/**
	 * 取得商品圖片
	 *
	 * @param \WC_Product $product 商品
	 * @param string|null $size 尺寸 full | single-post-thumbnail
	 * @param string|null $default_image 預設圖片
	 *
	 * @return string
	 */
	public static function get_image_url_by_product(
		\WC_Product $product,
		?string $size = 'full',
		?string $default_image = ''
	): string {
		$product_image = \wp_get_attachment_image_src(
			\get_post_thumbnail_id( $product->get_id() ),
			$size
		);

		if ( ! $product_image || ! is_array( $product_image ) ) {
			$product_image_url = $default_image ? $default_image : 'https://placehold.co/800x600?text=%3Cimg%20/%3E';
		} else {
			$product_image_url = $product_image[0];
		}

		return $product_image_url;
	}

	/**
	 * 檢查用戶是否購買過指定商品
	 *
	 * @param int|array<int>                            $target_product_ids 目標商品 ID
	 * @param array{user_id:string, status:string}|null $args 參數
	 * - user_id int 使用者 ID，預設 current_user_id
	 * - status string[]|string 訂單狀態 'any' | 'wc-completed' | 'wc-processing' | 'wc-on-hold' | 'wc-pending' | 'wc-cancelled' | 'wc-refunded' | 'wc-failed' , 預設 [ 'wc-completed' ]
	 *
	 * @return bool
	 */
	public static function has_bought( int|array $target_product_ids, ?array $args = [] ) {
		$has_bought = false;

		$customer_orders = \wc_get_orders(
			[
				'limit'       => - 1,
				'customer_id' => $args['user_id'] ?? \get_current_user_id(),
				'status'      => $args['status'] ?? [ 'wc-completed' ],
			]
		);
		foreach ( $customer_orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				/**
				 * @var \WC_Order_Item_Product $item
				 */
				$product_id = $item->get_product_id();

				if (is_array($target_product_ids)) {
					$has_bought = in_array( $product_id, $target_product_ids );
				} else {
					$has_bought = $product_id === $target_product_ids;
				}
			}
		}

		return $has_bought;
	}

	/**
	 * 取得商品屬性
	 *
	 * @param \WC_Product $product 商品
	 * @return array{name:string, options:array, position:int}[]
	 */
	public static function get_product_attribute_array( \WC_Product $product ): array {
		$attributes = $product->get_attributes(); // get attributes object

		$attributes_arr = [];

		foreach ( $attributes as $key => $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute ) {
				$attributes_arr[] = [
					'name'     => $attribute->get_name(),
					'options'  => $attribute->get_options(),
					'position' => $attribute->get_position(),
				];
			}

			if ( is_string( $key ) && is_string( $attribute ) ) {
				$attributes_arr[ urldecode( $key ) ] = $attribute;
			}
		}

		return $attributes_arr;
	}
}
