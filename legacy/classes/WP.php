<?php
/**
 * WP class
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'WP' ) ) {
	return;
}

/**
 * WP class
 */
abstract class WP {

	/**
	 * Admin do_shortcode function.
	 * 讓你可以在 wp-admin 後台也使用 do_shortcode
	 *
	 * @deprecated 使用 \J7\WpUtils\Shortcode::admin_do_shortcode 替代
	 *
	 * @example: WP::admin_do_shortcode( '[your-shortcode]' );
	 *
	 * @param string $content The shortcode
	 * @param ?bool  $ignore_html Whether to ignore HTML tags.
	 * @return string The processed content.
	 */
	public static function admin_do_shortcode( string $content, ?bool $ignore_html = false ) {
		return \J7\WpUtils\Shortcode::admin_do_shortcode( $content, $ignore_html );
	}


	/**
	 * Sanitize Array
	 * Sanitize 一個深層的關聯陣列
	 *
	 * @deprecated 使用 \J7\WpHelpers\Arr::sanitize 替代
	 *
	 * @param mixed         $value Value to sanitize
	 * @param bool          $allow_br 是否允許換行 \n \r，預設為 true，如果為 false，則會用 sanitize_text_field
	 * @param array<string> $skip_keys - 要跳過的 key，如果符合就不做任何 sanitize
	 * @return array<string, mixed>|string
	 */
	public static function sanitize_text_field_deep( $value, $allow_br = true, $skip_keys = [] ) {
		return \J7\WpHelpers\Arr::sanitize_text_field_deep( $value, $allow_br, $skip_keys );
	}

	/**
	 * 檢查關聯陣列是否包含了必要參數
	 *
	 * @deprecated 可以用 DTO 檢查
	 *
	 * @param array<string, mixed> $params - 要檢查的參數 assoc array
	 * @param array<string>        $required_params - string[] 必要參數
	 * @return true
	 * @throws \Exception 缺少必要參數
	 */
	public static function include_required_params( array $params, array $required_params ): bool {
		$missing_params = array_diff( $required_params, array_keys( $params ) );
		if ( ! empty( $missing_params ) ) {
			throw new \Exception( '缺少必要參數: ' . \wp_json_encode( $missing_params ) );
		}
		return true;
	}


	/**
	 * 將關聯陣列顯示為 HTML
	 *
	 * @deprecated 使用 \J7\WpHelpers\Arr::to_html 替代
	 *
	 * @param array<mixed> $arr - array
	 * @return string
	 */
	public static function array_to_table( array $arr ): string {
		return self::array_to_html($arr);
	}

	/**
	 * 將陣列轉換為 HTML 表格
	 *
	 * @deprecated 使用 \J7\WpHelpers\Arr::to_html 替代
	 * @param array<string, mixed> $arr 要轉換的陣列
	 * @param array{
	 *  title?: string,
	 *  br?: bool,
	 * } $options 選項
	 * @return string
	 */
	public static function array_to_html( array $arr, array $options = [] ): string {
		$arr_instance = \J7\WpHelpers\Arr::create( $arr );
		return $arr_instance->to_html( $options );
	}


	/**
	 * 分隔器，將陣列轉換成 data 與 meta data
	 * 因為 WP / WC 的資料通常區分成 data 與 meta data
	 *
	 * @param array<string, mixed>                                                                                                               $args - 原始資料
	 * @param string|null                                                                                                                        $obj_type - 'post' | 'term' | 'user' | 'product'
	 * @param array{tmp_name: string|string[], name: string|string[], type: string|string[], error: string|string[], size: string|string[]}|null $files - file 物件
	 * @return array{data: array<string, mixed>, meta_data: array<string, mixed>}
	 * @throws \Exception 上傳失敗
	 */
	public static function separator( array $args, ?string $obj_type = 'post', ?array $files = null ): array {

		if ( $files ) {
			$upload_results = self::upload_files( $files );
			$image_id       = $upload_results[0]['id'] ?? null;

			// 依照不同物件類型存入不同欄位
			if ( $image_id ) {
				$image_key = match ( $obj_type ) {
					'product' => 'image_id',
					'post' => '_thumbnail_id',
					default => '_thumbnail_id',
				};
				$args[ $image_key ] = $image_id;
			}
			// 如果上傳了多個檔案，例如商品 gallery 圖片
			if ( count( $upload_results ) > 1 ) {
				$gallery_image_ids         = array_map( fn( $result ) => $result['id'], array_slice( $upload_results, 1 ) );
				$args['gallery_image_ids'] = $gallery_image_ids;
			}
		}

		// 將資料拆成 data 與 meta_data
		$data      = [];
		$meta_data = [];

		$data_fields = self::get_data_fields( $obj_type );
		foreach ( $args as $key => $value ) {
			if ( \in_array( $key, $data_fields, true ) ) {
				$data[ $key ] = $value;
			} else {
				$meta_data[ $key ] = $value;
			}
		}

		return [
			'data'      => $data,
			'meta_data' => $meta_data,
		];
	}

	/**
	 * 取得 data fields
	 *
	 * @param string|null $obj - 'post' | 'term' | 'user' | 'product'
	 * @return string[]
	 */
	public static function get_data_fields( ?string $obj = 'post' ) {
		return match ( $obj ) {
			'post' => [
				'ID',
				'post_author',
				'post_date',
				'post_date_gmt',
				'post_content',
				'post_content_filtered',
				'post_title',
				'post_excerpt',
				'post_status',
				'post_type',
				'comment_status',
				'ping_status',
				'post_password',
				'post_name',
				'to_ping',
				'pinged',
				'post_parent',
				'menu_order',
				'post_mime_type',
				'guid',
				'import_id',
				'post_category',
				'tags_input',
				'tax_input',
				'page_template',
			],
			'product' => [
				'attributes',
				'average_rating',
				'backorders',
				'catalog_visibility',
				'category_ids',
				'cross_sell_ids',
				'date_created',
				'date_modified',
				'date_on_sale_from',
				'date_on_sale_to',
				'default_attributes',
				'defaults',
				'description',
				'download_expiry',
				'download_limit',
				'downloadable',
				'downloads',
				'featured',
				'gallery_image_ids',
				'height',
				'id',
				'image_id',
				'length',
				'low_stock_amount',
				'manage_stock',
				'menu_order',
				'meta_data',
				'name',
				'object_read',
				'parent_id',
				'post_password',
				'price',
				'props',
				'purchase_note',
				'rating_counts',
				'regular_price',
				'review_count',
				'reviews_allowed',
				'sale_price',
				'shipping_class_id',
				'short_description',
				'sku',
				'slug',
				'sold_individually',
				'status',
				'stock',
				'stock_quantity',
				'stock_status',
				'tag_ids',
				'tax_class',
				'tax_status',
				'total_sales',
				'upsell_ids',
				'virtual',
				'weight',
				'width',
			],
			'user' => [
				'ID',
				'user_pass',
				'user_login',
				'user_nicename',
				'user_url',
				'user_email',
				'display_name',
				'nickname',
				'first_name',
				'last_name',
				'description',
				'rich_editing',
				'syntax_highlighting',
				'comment_shortcuts',
				'admin_color',
				'use_ssl',
				'user_registered',
				'show_admin_bar_front',
				'role',
				'locale',
			],
			'comment' => [
				'comment_post_ID',
				'comment_author',
				'comment_author_email',
				'comment_author_url',
				'comment_author_IP',
				'comment_date',
				'comment_date_gmt',
				'comment_content',
				'comment_karma',
				'comment_approved',
				'comment_agent',
				'comment_type',
				'comment_parent',
				'comment_meta',
				'user_id',
			],
			'order' => [
				'address',
				'billing',
				'billing_address',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_company',
				'billing_country',
				'billing_email',
				'billing_first_name',
				'billing_last_name',
				'billing_phone',
				'billing_postcode',
				'billing_state',
				'cart_hash',
				'cart_tax',
				'created_via',
				'currency',
				'customer_id',
				'customer_ip_address',
				'customer_note',
				'customer_user_agent',
				'date_completed',
				'date_created',
				'date_modified',
				'date_paid',
				'defaults',
				'discount_tax',
				'discount_total',
				'download_permissions_granted',
				'id',
				'meta_data',
				'new_order_email_sent',
				'object_read',
				'order_key',
				'order_stock_reduced',
				'parent_id',
				'payment_method',
				'payment_method_title',
				'prices_include_tax',
				'props',
				'recorded_coupon_usage_counts',
				'recorded_sales',
				'shipping',
				'shipping_address',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_company',
				'shipping_country',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_phone',
				'shipping_postcode',
				'shipping_state',
				'shipping_tax',
				'shipping_total',
				'status',
				'total',
				'transaction_id',
				'version',
				'order_version',
			],
			'term' => [
				'name',
				'slug',
				'description',
				'alias_of',
				'parent',
			],
			default => [],
		};
	}

	/**
	 * 取得 image info
	 *
	 * @param string|int $attachment_id - 附件 ID
	 * @return array{id: string, url: string}|null
	 */
	public static function get_image_info( $attachment_id ): array|null {
		$image_url = \wp_get_attachment_url( (int) $attachment_id );
		if ( ! $image_url ) {
			return null;
		}
		return [
			'id'  => (string) $attachment_id,
			'url' => $image_url,
		];
	}



	/**
	 * 將檔案上傳到媒體庫
	 *
	 * @param array{tmp_name: string|string[], name: string|string[], type: string|string[], error: string|string[], size: string|string[]} $files - $_FILES
	 * @param bool                                                                                                                          $upload_only - 是否只上傳到 wp-content/uploads 而不新增到媒體庫
	 * @return array<int, array{id: string|null, url: string, type: string, name: string, size: string}>
	 * @throws \Exception 上傳失敗
	 */
	public static function upload_files( array $files, ?bool $upload_only = false ): array {

		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once 'wp-admin/includes/image.php';
			require_once 'wp-admin/includes/file.php';
			require_once 'wp-admin/includes/media.php';
		}

		$is_multiple_files = is_array( $files['tmp_name'] );

		if ( $is_multiple_files ) {
			/** @var array{tmp_name:string[], name:string[], type:string[], error:string[], size:string[]} $files */
			$upload_results = self::handle_multiple_files_to_media( $files, $upload_only );
		} else {
			/** @var array{tmp_name:string, name:string, type:string, error:string, size:string} $files */
			$upload_results = self::handle_single_files_to_media( $files, $upload_only );
		}

		return $upload_results;
	}

	/**
	 * Upload base64 image to media
	 *
	 * @param string $base64_img Base64 image.
	 * @param string $filename Filename.
	 * @param ?bool  $upload_only Upload only.
	 * @return array{id: int<0, max>|null, url: non-falsy-string, type: 'image/gif'|'image/jpeg'|'image/png'|'image/webp', name: string|null, size: int<1, max>}
	 * @throws \Exception 圖片格式錯誤
	 */
	public static function upload_single_base64_image( string $base64_img, string $filename = 'unknown', $upload_only = false ): array {
		// Upload dir
		$upload_dir  = \wp_upload_dir();
		$upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;

		// 檢測圖片格式
		preg_match('/data:image\/(.*?);base64,/', $base64_img, $image_extension);

		// 獲取檔案格式
		$file_type = '';
		$extension = '';
		if (isset($image_extension[1])) {
			$extension = $image_extension[1];
			switch ($extension) {
				case 'jpeg':
				case 'jpg':
					$file_type = 'image/jpeg';
					$extension = 'jpg';
					break;
				case 'png':
					$file_type = 'image/png';
					break;
				case 'gif':
					$file_type = 'image/gif';
					break;
				case 'webp':
					$file_type = 'image/webp';
					break;
				default:
					throw new \Exception('不支援的圖片格式');
			}
		} else {
			throw new \Exception('無效的 base64 圖片格式');
		}

		// 移除 base64 頭部標識
		/** @var string $img */
		$img     = preg_replace('/data:image\/(.*?);base64,/', '', $base64_img);
		$img     = str_replace(' ', '+', $img);
		$decoded = base64_decode($img);

		// 使用原始的副檔名
		$filename        = "{$filename}.{$extension}";
		$hashed_filename = md5($filename . microtime()) . '_' . $filename;

		// 儲存圖片到上傳目錄
		$upload_file = file_put_contents($upload_path . $hashed_filename, $decoded);

		if (!$upload_file) {
			throw new \Exception('圖片儲存失敗');
		}

		$attachment = [
			'post_mime_type' => $file_type,
			'post_title'     => preg_replace('/\.[^.]+$/', '', basename($hashed_filename)),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => $upload_dir['url'] . '/' . basename($hashed_filename),
		];

		if ($upload_only) {
			return [
				'id'   => null,
				'url'  => $attachment['guid'],
				'type' => $attachment['post_mime_type'],
				'name' => $attachment['post_title'],
				'size' => $upload_file,
			];
		}

		$attach_id = \wp_insert_attachment($attachment, $upload_dir['path'] . '/' . $hashed_filename);

		// 如果是圖片，生成縮圖
		if ($attach_id && function_exists('wp_generate_attachment_metadata')) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attach_data = \wp_generate_attachment_metadata($attach_id, $upload_dir['path'] . '/' . $hashed_filename);
			\wp_update_attachment_metadata($attach_id, $attach_data);
		}

		return [
			'id'   => $attach_id,
			'url'  => $attachment['guid'],
			'type' => $attachment['post_mime_type'],
			'name' => $attachment['post_title'],
			'size' => $upload_file,
		];
	}

	/**
	 * 將單個檔案上傳到媒體庫
	 *
	 * @param array{tmp_name: string, name: string, type: string, error: string, size: string} $file - $_FILES
	 * @param ?bool                                                                            $upload_only - 是否只上傳到 wp-content/uploads 而不新增到媒體庫
	 * @return array{0: array{id: string|null, url: string, type: string, name: string, size: string}}
	 * @throws \Exception 上傳失敗
	 */
	public static function handle_single_files_to_media( array $file, ?bool $upload_only = false ): array {
		$upload_results   = [];
		$upload_overrides = [ 'test_form' => false ];

		$_FILES         = [];
		$_FILES['file'] = $file;

		if ( $upload_only ) {
			// 直接上傳到 wp-content/uploads 不會新增到媒體庫
			$upload_result = \wp_handle_upload( $file, $upload_overrides );
			unset( $upload_result['file'] );
			$upload_result['id']   = null;
			$upload_result['type'] = $file['type'];
			$upload_result['name'] = $file['name'];
			$upload_result['size'] = $file['size'];
			if ( isset( $upload_result['error'] ) ) {
				throw new \Exception( $upload_result['error'] ); // phpcs:ignore
			}
		} else {
			// 將檔案上傳到媒體庫
			$attachment_id = \media_handle_upload(
			file_id: 'file',
			post_id: 0
			);

			if ( \is_wp_error( $attachment_id ) ) {
				throw new \Exception( $attachment_id->get_error_message() ); // phpcs:ignore
			}

			$upload_result = [
				'id'   => (string) $attachment_id,
				'url'  => \wp_get_attachment_url( $attachment_id ),
				'type' => $file['type'],
				'name' => $file['name'],
				'size' => $file['size'],
			];
		}

		$upload_results[] = $upload_result;

		/** @var array{0: array{id: string|null, url: string, type: string, name: string, size: string}} */
		return $upload_results;
	}

	/**
	 * 將多個檔案上傳到媒體庫
	 *
	 * @param array{tmp_name: string[], name: string[], type: string[], error: string[], size: string[]} $files - $_FILES
	 * @param ?bool                                                                                      $upload_only - 是否只上傳到 wp-content/uploads 而不新增到媒體庫
	 * @return array<int, array{id: string|null, url: string, type: string, name: string, size: string}>
	 * @throws \Exception 上傳失敗
	 */
	public static function handle_multiple_files_to_media( $files, $upload_only = false ) {
		$upload_results   = [];
		$upload_overrides = [ 'test_form' => false ];
		$_FILES           = [];

		// 遍歷每個上傳的檔案
		foreach ( $files['tmp_name'] as $key => $tmp_name ) {
			if ( ! empty( $tmp_name ) ) {
				$file = [
					'name'     => $files['name'][ $key ],
					'type'     => $files['type'][ $key ],
					'tmp_name' => $tmp_name,
					'error'    => $files['error'][ $key ],
					'size'     => $files['size'][ $key ],
				];

				$_FILES[ $key ] = $file;

				if ( $upload_only ) {
					// 直接上傳到 wp-content/uploads 不會新增到媒體庫
					$upload_result = \wp_handle_upload( $file, $upload_overrides );
					unset( $upload_result['file'] );
					$upload_result['id']   = null;
					$upload_result['type'] = $file['type'];
					$upload_result['name'] = $file['name'];
					$upload_result['size'] = $file['size'];
					if ( isset( $upload_result['error'] ) ) {
						throw new \Exception( $upload_result['error'] );  // phpcs:ignore
					}
				} else {
					// 將檔案上傳到媒體庫
					$attachment_id = \media_handle_upload(
						file_id: $key,
						post_id: 0
					);

					if ( \is_wp_error( $attachment_id ) ) {
						throw new \Exception( $attachment_id->get_error_message() );  // phpcs:ignore
					}

					$upload_result = [
						'id'   => (string) $attachment_id,
						'url'  => \wp_get_attachment_url( $attachment_id ),
						'type' => $file['type'],
						'name' => $file['name'],
						'size' => $file['size'],
					];
				}

				$upload_results[] = $upload_result;
			}
		}

		/** @var array<int, array{id: string|null, url: string, type: string, name: string, size: string}> */
		return $upload_results;
	}

	/**
	 * 判斷內容是否包含短碼
	 *
	 * @deprecated 使用 \J7\WpUtils\Shortcode::has_shortcode 替代
	 *
	 * @param string $content 內容
	 *
	 * @return bool
	 */
	public static function has_shortcode( string $content ): bool {
		return \J7\WpUtils\Shortcode::has_shortcode( $content );
	}


	/**
	 * Converter 轉換器
	 * 把 key 轉換/重新命名，將 前端傳過來的欄位轉換成 wp_update_post 能吃的參數
	 * 如果不轉換的欄位就指定為 key => null
	 *
	 * 前端圖片欄位就傳 'image_ids' string[] 就好
	 *
	 * @param array<string, mixed>  $args    Arguments.
	 * @param array<string, string> $fields_mapper 欄位轉換器
	 *
	 * @return array<string, mixed>
	 */
	public static function converter( array $args, ?array $fields_mapper = [] ): array {
		$default_fields_mapper = [
			'id'                => 'ID',
			'name'              => 'post_title',
			'slug'              => 'post_name',
			'description'       => 'post_content',
			'short_description' => 'post_excerpt',
			'status'            => 'post_status',
			'category_ids'      => 'post_category',
			'tag_ids'           => 'tags_input',
			'parent_id'         => 'post_parent',
			'depth'             => 'unset',
		];

		$fields_mapper = \wp_parse_args(
			$fields_mapper, // @phpstan-ignore-line
			$default_fields_mapper,
		);

		$formatted_args = [];
		foreach ($args as $key => $value) {
			if (in_array($key, array_keys($fields_mapper), true)) {
				if (null === $fields_mapper[ $key ]) {
					continue;
				}
				$formatted_args[ $fields_mapper[ $key ] ] = $value;
			} else {
				$formatted_args[ $key ] = $value;
			}
		}

		return $formatted_args;
	}


	/**
	 * 將 local 時間字串轉換成 timestamp
	 *
	 * @deprecated 使用 \J7\WpUtils\Time::wp_strtotime 替代
	 *
	 * @param string $date_string 時間字串
	 * @return int|null
	 */
	public static function wp_strtotime( string $date_string ): int|null {
		return \J7\WpUtils\Time::wp_strtotime( $date_string );
	}


	/**
	 * 判斷資料庫 table 是否存在
	 *
	 * @deprecated 使用 \J7\WpHelpers\Table 替代
	 *
	 * @param string $table_name 表格名稱 (含 wp_ 前綴)
	 * @return bool
	 */
	public static function is_table_exists( string $table_name ): bool {
		$table = new \J7\WpHelpers\Table( $table_name );
		return $table->is_exists();
	}
}
