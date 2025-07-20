<?php

namespace J7\WpServices\Log\Core;

/**
 * PointType CPT 單例
 * 點數系統初始化，建立 PointType CPT
 * 如果 post_type 已經存在可以複寫
 * 例如
 * 紅利積點，有很多不同種類的紅利積點
 * 購物金，有很多不同種類的購物金
 */
abstract class RegisterPointTypeCPT {
	use \J7\WpUtils\Traits\SingletonTrait;

	/** @var string 點數類型 CPT */
	public $post_type = 'j7_point_type';

	/** @var array 點數類型 CPT 標籤 */
	protected array $labels = [];

	/** @var array 點數類型 CPT 參數 */
	protected array $args = [];

	/** Constructo */
	public function __construct() {
		\add_action('init', [ $this, 'register_post_type' ], 10);
		\add_filter('wp_insert_post_data', [ $this, 'filter_point_slug' ], 100, 4);
	}


	/**
	 * 註冊 post type
	 *
	 * @return void
	 * @throws \Exception 註冊失敗
	 */
	public function register_post_type(): void {
		try {
			$is_post_type_exists = \post_type_exists($this->post_type);
			if ($is_post_type_exists) {
				throw new \Exception('已經被註冊，請覆寫 $post_type');
			}

			$default_args = [
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 70,
				'menu_icon'          => 'dashicons-awards',
				'supports'           => [ 'title', 'thumbnail', 'custom-fields' ],
			];

			$args = \wp_parse_args( $this->args, $default_args );

			if ($this->labels) {
				$args['labels'] = $this->labels;
			}

			$result = \register_post_type($this->post_type, $args);
			if (\is_wp_error($result)) {
				throw new \Exception($result->get_error_message());
			}
		} catch (\Throwable $th) {
			\J7\WpUtils\Classes\WC::logger("post_type {$this->post_type} 註冊失敗: {$th->getMessage()}", 'error' );
		}
	}

	/**
	 * 過濾點數 slug
	 * 因為如果 point slug 如果是 urlencode 的話，會出錯
	 *
	 * @param array $data                已經過處理、消毒和斜線處理的文章資料陣列
	 * @param array $postarr             已消毒（和斜線處理）但未經修改的文章資料陣列
	 * @param array $unsanitized_postarr 已斜線處理但未消毒和未處理的文章資料陣列，
	 *                                   這是最初傳遞給 wp_insert_post() 的原始資料
	 * @param bool  $update              是否為更新現有文章
	 *
	 * @return array
	 */
	public function filter_point_slug( $data, $postarr, $unsanitized_postarr, $update ): array {
		$origin_slug = $data['post_name'];
		if ($this->post_type !== $data['post_type']) {
			return $data;
		}

		// 如果是單純用 英文數字 - _ 組成
		if (preg_match('/^[a-zA-Z0-9\-\_]+$/', $origin_slug) === 1) {
			return $data;
		}

		// 如果不是單純用 英文數字 - _ 組成，則轉換為英文數字 - _
		$data['post_name'] = $postarr['ID'];
		return $data;
	}
}
