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
abstract class WC
{

	/**
	 * Is HPOS enabled
	 *
	 * @return bool
	 */
	public static function is_hpos_enabled(): bool
	{
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
	public static function get_top_sales_products($limit = 10)
	{
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
			function ($product) {
				$product_id   = $product->post_id;
				$product_name = \get_the_title($product_id);
				$total_sales  = $product->total_sales;

				return array(
					'id'          => (string) $product_id,
					'name'        => $product_name,
					'total_sales' => (float) $total_sales,
				);
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
	public static function get_formatted_meta_data(\WC_Product $product): array
	{
		$meta_data           = $product->get_meta_data();
		$formatted_meta_data = array();
		foreach ($meta_data as $meta) {
			$formatted_meta_data[$meta->key] = $meta->value;
		}

		return $formatted_meta_data;
	}

    /**
     * 用產品反查訂單
     * TODO 支援 HPOS
     *
     * @param int        $product_id 產品 ID
     * @param array|null $args 參數
     * - user_id int 使用者 ID
     * - limit int 查詢筆數
     * - order_statuses string[] 訂單狀態, 預設 [ 'wc-completed', 'wc-processing' ]
     *
     * @return array string[] order_ids
     */
    public static function get_order_ids_by_product_id( int $product_id, ?array $args ): array {
        global $wpdb;
        $user_id               = $args['user_id'] ?? \get_current_user_id();
        $limit                 = $args['limit'] ?? 10;
        $order_statuses        = $args['order_statuses'] ?? [ 'wc-completed', 'wc-processing' ];
        $order_statuses_string = implode( ',', array_map( function ( $status ) {
            return '"' . $status . '"';
        }, $order_statuses ) );

        try {
            $prepare = $wpdb->prepare(
                "
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_author = %1\$s
          AND posts.post_status IN ( %2\$s )
          AND order_items.order_item_type = 'line_item'
          AND order_item_meta.meta_key = '_product_id'
          AND order_item_meta.meta_value = %3\$s
        ORDER BY order_items.order_id DESC
        LIMIT %4\$s",
                $user_id,
                $order_statuses_string,
                $product_id,
                $limit
            );

            return $wpdb->get_col( str_replace( '\"', '"', $prepare ) );
        } catch ( \Exception $e ) {
            \J7\WpUtils\Classes\Log::info( $e->getMessage() );

            return [];
        }
    }
}
