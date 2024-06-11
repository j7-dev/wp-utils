<?php
/**
 * WC class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'WC' ) ) {
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
		return class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
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
				$product_name = \get_the_title( $product_id );
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
	public static function get_formatted_meta_data( \WC_Product $product ): array {
		$meta_data           = $product->get_meta_data();
		$formatted_meta_data = array();
		foreach ( $meta_data as $meta ) {
			$formatted_meta_data[ $meta->key ] = $meta->value;
		}

		return $formatted_meta_data;
	}
}
