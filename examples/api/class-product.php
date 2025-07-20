<?php
/**
 * Products Api 商品 API
 */

declare(strict_types=1);

namespace J7\WpUtils\Api;

use J7\WpUtils\Classes\WC;
use J7\WpUtils\Classes\WP;

/**
 * Class product
 */
final class Product {

	use \J7\WpUtils\SingletonTrait;

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', [ $this, 'register_api_product' ] );
	}

	/**
	 * Register products API
	 *
	 * @return void
	 */
	public function register_api_product(): void {

		$apis = [
			[
				'endpoint'            => 'products',
				'method'              => 'get',
				'permission_callback' => function () {
						return \current_user_can( 'manage_options' );
				},
			],
			[
				'endpoint'            => 'terms',
				'method'              => 'get',
				'permission_callback' => function () {
						return \current_user_can( 'manage_options' );
				},
			],
			[
				'endpoint'            => 'options',
				'method'              => 'get',
				'permission_callback' => function () {
						return \current_user_can( 'manage_options' );
				},
			],
		];

		foreach ( $apis as $api ) {
			// 用正則表達式替換 -, / 替換為 _
			$endpoint_fn = str_replace( '(?P<id>\d+)', 'with_id', $api['endpoint'] );
			$endpoint_fn = preg_replace( '/[-\/]/', '_', $endpoint_fn );
			\register_rest_route(
			'wp-utils/v1',
			$api['endpoint'],
			[
				'methods'             => $api['method'],
				'callback'            => [ $this, $api['method'] . '_' . $endpoint_fn . '_callback' ],
				'permission_callback' => $api['permission_callback'],
			]
			);
		}
	}


	/**
	 * Get products callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_products_callback( $request ) { // phpcs:ignore

		$params = $request->get_query_params() ?? [];

		$params = array_map( [ WP::class, 'sanitize_text_field_deep' ], $params );

		$default_args = [
			'status'         => 'publish',
			'paginate'       => true,
			'posts_per_page' => 10,
			'page'           => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		$args = \wp_parse_args(
		$params,
		$default_args,
		);

		$results     = \wc_get_products( $args );
		$total       = $results->total;
		$total_pages = $results->max_num_pages;

		$products = $results->products;

		$formatted_products = array_map( [ $this, 'format_product_details' ], $products );

		$response = new \WP_REST_Response( $formatted_products );

		// set pagination in header
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Format product details
	 * TODO
	 *
	 * @see https://www.businessbloomer.com/woocommerce-easily-get-product-info-title-sku-desc-product-object/
	 *
	 * @param \WC_Product $product Product.
	 * @param bool        $with_description With description.
	 * @return array
	 */
	public function format_product_details( $product , $with_description = false) { // phpcs:ignore

		if ( ! ( $product instanceof \WC_Product ) ) {
			return [];
		}

		$date_created  = $product->get_date_created();
		$date_modified = $product->get_date_modified();

		$image_id  = $product->get_image_id();
		$image_url = \wp_get_attachment_url( $image_id );

		$gallery_image_ids  = $product->get_gallery_image_ids();
		$gallery_image_urls = array_map( 'wp_get_attachment_url', $gallery_image_ids );

		$description_array = $with_description ? [
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
		] : [];

		$low_stock_amount = ( '' === $product->get_low_stock_amount() ) ? null : $product->get_low_stock_amount();

		$variation_ids = $product->get_children(); // get variations
		$children      = [];
		if ( ! empty( $variation_ids ) ) {
			$variation_products = array_map( 'wc_get_product', $variation_ids );
			$children_details   = array_map( [ $this, 'format_product_details' ], $variation_products );
			$children           = [
				'children'  => $children_details,
				'parent_id' => (string) $product->get_id(),
			];
		}

		$base_array = [
			// Get Product General Info
			'id'                 => (string) $product->get_id(),
			'type'               => $product->get_type(),
			'name'               => $product->get_name(),
			'slug'               => $product->get_slug(),
			'date_created'       => $date_created->date( 'Y-m-d H:i:s' ),
			'date_modified'      => $date_modified->date( 'Y-m-d H:i:s' ),
			'status'             => $product->get_status(),
			'featured'           => $product->get_featured(),
			'catalog_visibility' => $product->get_catalog_visibility(),
			'sku'                => $product->get_sku(),
			// 'menu_order'         => $product->get_menu_order(),
			'virtual'            => $product->get_virtual(),
			'downloadable'       => $product->get_downloadable(),
			'permalink'          => get_permalink( $product->get_id() ),

			// Get Product Prices
			'price_html'         => $product->get_price_html(),
			'regular_price'      => $product->get_regular_price(),
			'sale_price'         => $product->get_sale_price(),
			'on_sale'            => $product->is_on_sale(),
			'date_on_sale_from'  => $product->get_date_on_sale_from(),
			'date_on_sale_to'    => $product->get_date_on_sale_to(),
			'total_sales'        => $product->get_total_sales(),

			// Get Product Stock
			'stock'              => $product->get_stock_quantity(),
			'stock_status'       => $product->get_stock_status(),
			'manage_stock'       => $product->get_manage_stock(),
			'stock_quantity'     => $product->get_stock_quantity(),
			'backorders'         => $product->get_backorders(),
			'backorders_allowed' => $product->backorders_allowed(),
			'backordered'        => $product->is_on_backorder(),
			'low_stock_amount'   => $low_stock_amount,

			// Get Linked Products
			'upsell_ids'         => array_map( 'strval', $product->get_upsell_ids() ),
			'cross_sell_ids'     => array_map( 'strval', $product->get_cross_sell_ids() ),

			// Get Product Variations and Attributes
			'attributes'         => $product->get_attributes(), // TODO
			'default_attributes' => $product->get_default_attributes(), // TODO
			'attribute'          => $product->get_attribute( 'attributeid' ), // TODO get specific attribute value

		// Get Product Taxonomies
			'category_ids'       => array_map( 'strval', $product->get_category_ids() ),
			'tag_ids'            => array_map( 'strval', $product->get_tag_ids() ),

			// Get Product Images
			'image_url'          => $image_url,
			'gallery_image_urls' => $gallery_image_urls,

		// variations
		// 'children'           => $children,
		] + $children;

		return array_merge(
		$description_array,
		$base_array
		);
	}

	/**
	 * Get terms callback
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_terms/
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function get_terms_callback( $request ) { // phpcs:ignore

		$params = $request->get_query_params() ?? [];

		$params = array_map( [ WP::class, 'sanitize_text_field_deep' ], $params );

		// it seems no need to add post_per_page, get_terms will return all terms
		$default_args = [
			'taxonomy'   => 'product_cat',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		$args = \wp_parse_args(
		$params,
		$default_args,
		);

		$terms = \get_terms( $args );

		$formatted_terms = array_map( [ $this, 'format_terms' ], array_keys( $terms ), array_values( $terms ) );

		return $formatted_terms;
	}

	/**
	 * Format terms
	 *
	 * @param string $key Key.
	 * @param string $value Value.
	 * @return array
	 */
	public function format_terms( $key, $value ) {
		return [
			'id'   => (string) $key,
			'name' => $value,
		];
	}

	/**
	 * Get options callback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function get_options_callback( $request ) { // phpcs:ignore

		// it seems no need to add post_per_page, get_terms will return all terms
		$cat_args = [
			'taxonomy'   => 'product_cat',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];
		$cats     = \get_terms( $cat_args );

		$formatted_cats = array_map( [ $this, 'format_terms' ], array_keys( $cats ), array_values( $cats ) );

		$tag_args = [
			'taxonomy'   => 'product_tag',
			'fields'     => 'id=>name',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		$tags = \get_terms( $tag_args );

		$formatted_tags = array_map( [ $this, 'format_terms' ], array_keys( $tags ), array_values( $tags ) );

		$top_sales_products = WC::get_top_sales_products( 5 );

		return [
			'product_cats'       => $formatted_cats,
			'product_tags'       => $formatted_tags,
			'top_sales_products' => $top_sales_products,
		];
	}
}

product::instance();
