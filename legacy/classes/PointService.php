<?php
/**
 * PointService  class
 * 點數系統初始化，創建 CPT
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

use Exception;

if (class_exists('PointService')) {
	return;
}

/**
 * Class Point
 */
final class PointService {

	use \J7\WpUtils\Traits\SingletonTrait;

	public const POST_TYPE = 'wpu_point';
	public $default_point  = null;

	/**
	 * Log instance
	 *
	 * @var LogService
	 */
	public LogService $log_instance;

	public function init( LogService $log_instance ): void {
		\add_action('init', [ $this, 'register_post_type' ], 10);
		\add_action('init', [ $this, 'create_default_point' ], 20);
		$this->log_instance = $log_instance;

		\add_filter('pre_wp_unique_post_slug', [ $this, 'filter_point_slug' ], 99, 6);

		$all_points = $this->get_all_points();
		foreach ($all_points as $point) {
			\add_action('user_' . $point->slug . '_points_updated', [ $this, 'insert_log' ], 10, 4);
		}
	}

	/**
	 * Get all points
	 *
	 * @param string|null $post_status Post status (default: publish)
	 *
	 * @return array Point[]
	 */
	public function get_all_points( ?string $post_status = 'publish' ): array {
		$all_point_posts = \get_posts(
			[
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => $post_status,
				'order'          => 'ASC',
			]
		);

		return array_map(
			function ( $post ) {
				return new Point($post);
			},
			$all_point_posts
		);
	}

	/**
	 * Insert log
	 *
	 * @param int    $user_id User id
	 * @param array  $args Args
	 * @param float  $points Points
	 * @param string $point_slug Point slug
	 *
	 * @return void
	 * @throws Exception Exception.
	 */
	public function insert_log( int $user_id, array $args, float $points, string $point_slug ): void {
		$this->log_instance->insert_user_log($user_id, $args, $points, $point_slug);
	}

	/**
	 * Register post type
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$is_post_type_exists = \post_type_exists(self::POST_TYPE);
		if ($is_post_type_exists) {
			return;
		}
		$labels = [
			'name'                  => _x('Points', 'Post type general name', 'wp-utils'),
			'singular_name'         => _x('Point', 'Post type singular name', 'wp-utils'),
			'menu_name'             => _x('Points', 'Admin Menu text', 'wp-utils'),
			'name_admin_bar'        => _x('Point', 'Add New on Toolbar', 'wp-utils'),
			'add_new'               => __('Add New', 'wp-utils'),
			'add_new_item'          => __('Add New Point', 'wp-utils'),
			'new_item'              => __('New Point', 'wp-utils'),
			'edit_item'             => __('Edit Point', 'wp-utils'),
			'view_item'             => __('View Point', 'wp-utils'),
			'all_items'             => __('All Points', 'wp-utils'),
			'search_items'          => __('Search Points', 'wp-utils'),
			'parent_item_colon'     => __('Parent Points:', 'wp-utils'),
			'not_found'             => __('No books found.', 'wp-utils'),
			'not_found_in_trash'    => __('No books found in Trash.', 'wp-utils'),
			'featured_image'        => _x(
				'Point Cover Image',
				'Overrides the “Featured Image” phrase for this post type. Added in 4.3',
				'wp-utils'
			),
			'set_featured_image'    => _x(
				'Set cover image',
				'Overrides the “Set featured image” phrase for this post type. Added in 4.3',
				'wp-utils'
			),
			'remove_featured_image' => _x(
				'Remove cover image',
				'Overrides the “Remove featured image” phrase for this post type. Added in 4.3',
				'wp-utils'
			),
			'use_featured_image'    => _x(
				'Use as cover image',
				'Overrides the “Use as featured image” phrase for this post type. Added in 4.3',
				'wp-utils'
			),
			'archives'              => _x(
				'Point archives',
				'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4',
				'wp-utils'
			),
			'insert_into_item'      => _x(
				'Insert into book',
				'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4',
				'wp-utils'
			),
			'uploaded_to_this_item' => _x(
				'Uploaded to this book',
				'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4',
				'wp-utils'
			),
			'filter_items_list'     => _x(
				'Filter books list',
				'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4',
				'wp-utils'
			),
			'items_list_navigation' => _x(
				'Points list navigation',
				'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4',
				'wp-utils'
			),
			'items_list'            => _x(
				'Points list',
				'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4',
				'wp-utils'
			),
		];

		$args = [
			'labels'             => $labels,
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

		\register_post_type(self::POST_TYPE, $args);
	}

	/**
	 * Get default point
	 *
	 * @deprecated
	 *
	 * @return Point|null
	 */
	public function get_default_point(): ?Point {
		return $this->default_point;
	}

	/**
	 * Get point by slug
	 *
	 * @param string $slug Point slug
	 *
	 * @return Point|null
	 */
	public function get_point_by_slug( string $slug ): ?Point {
		$all_points = $this->get_all_points();
		$point      = \array_filter(
			$all_points,
			function ( $point ) use ( $slug ) {
				return $point->slug === $slug;
			}
		);

		if (!$point) {
			return null;
		}

		return \reset($point);
	}

	/**
	 * Filter point slug
	 * 因為如果 point slug 如果是 urlencode 的話，會出錯
	 *
	 * @param string|null $override_slug
	 * @param string      $slug
	 * @param int         $post_id
	 * @param string      $post_status
	 * @param string      $post_type
	 * @param int         $post_parent
	 *
	 * @return string|null
	 */
	public function filter_point_slug(
		?string $override_slug,
		string $slug,
		int $post_id,
		string $post_status,
		string $post_type,
		int $post_parent
	): ?string {
		if (self::POST_TYPE !== $post_type) {
			return $override_slug;
		}

		return 'wpu_point_' . $post_id;
	}

	/**
	 * 創建預設點數
	 * 如果沒有 default 點數，則創建一個
	 *
	 * @return void
	 */
	public function create_default_point(): void {
		$post_type = self::POST_TYPE;
		$posts     = get_posts(
			[
				'post_type'   => $post_type,
				'post_status' => 'any',
				'numberposts' => 1,
				'order'       => 'ASC',
			]
		);

		if (!!$posts && is_array($posts)) {
			$post                = $posts[0];
			$point               = new Point($post);
			$this->default_point = $point;
		} else {
			// create default member_lv
			$post_id             = \wp_insert_post(
			[
				'post_title'  => '購物金',
				'post_type'   => $post_type,
				'post_status' => 'publish',
			]
			);
			$post                = get_post($post_id);
			$this->default_point = $post;
			// TODO 設定預設圖片
		}
	}
}
