<?php

namespace J7\WpServices\Log\Model;

use J7\WpAbstracts\DTO;

/**
 * PointType
 * PointType 本身是一個 CPT
 * 點數類型，用戶可以自己新增點數類型
 * 例如，紅利點數、積分、現金點數、購物金
 */
class PointType extends DTO {

	/** @var int 點數類型 ID */
	public int $id;

	/** @var string 點數類型名稱 */
	public string $name;

	/** @var string 點數類型唯一識別 */
	public string $slug;

	/** @var string 點數類型圖示 */
	public string $icon_url;

	/** @var string 點數類型描述 */
	public string $description;

	/** @var string 點數類型簡短描述 */
	public string $short_description;

	/** @var int 點數類型排序 */
	public int $menu_order;

	/** @var string 點數類型的 CPT */
	protected $post_type;


	/**
	 * 取得實例
	 *
	 * @param \WP_Post $post 點數類型文章
	 * @return self
	 */
	public static function create( \WP_Post $post ): self {
		$feature_image = \get_post_thumbnail_id( $post->ID );
		$icon_url      = \wp_get_attachment_image_url( $feature_image, 'full' );
		$args          = [
			'id'                => $post->ID,
			'name'              => $post->post_title,
			'slug'              => $post->post_name,
			'icon_url'          => $icon_url,
			'description'       => \get_post_field( 'post_content', $post->ID ),
			'short_description' => \get_post_field( 'post_excerpt', $post->ID ),
			'menu_order'        => $post->menu_order,
			'post_type'         => $post->post_type,
		];
		return new self( $args );
	}
}
