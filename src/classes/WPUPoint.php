<?php
/**
 * WPUPoint  class
 * 點數物件
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'WPUPoint ' ) ) {
	return;
}


/**
 * Class Point
 */
final class WPUPoint {
	public $id;
	public $name = '';
	public $status = 'publish';
	public $slug = '';
	public $order = 0;


	/**
	 * Constructor
	 *
	 * @param \WP_Post $post Point post object
	 */
	public function __construct( \WP_Post $post ) { // phpcs:ignore
		$this->id     = $post->ID;
		$this->name   = $post->post_title;
		$this->status = $post->post_status;
		$this->slug   = $post->post_name;
		$this->order  = $post->menu_order;
	}


	/**
	 * 加多少點
	 *
	 * @param int $user_id - user id
	 * @param array $args - args
	 * @param float $points - points
	 *
	 * @return float updated points value
	 */
	public function award_points_to_user( int $user_id = 0, array $args = [], float $points = 0 ): float {
		// If point amount is negative, turn them to positive
		if ( $points < 0 ) {
			$points *= - 1;
		}

		// Use current user's ID if none specified
		if ( ! $user_id ) {
			$user_id = \get_current_user_id();
		}
		$point_slug     = $this->slug;
		$current_points = (float) \get_user_meta( $user_id, $point_slug, true );
		$points         = $current_points + $points;

		return $this->update_user_points( $user_id, $args, $points );
	}

	/**
	 * 直接更新點數到某個值
	 *  TODO Mysql transaction
	 *
	 * @param int $user_id - user id
	 * @param array $args - args
	 * @param float $points - points
	 *
	 * @return float updated points value
	 */
	public function update_user_points( int $user_id = 0, array $args = [], float $points = 0 ): float {
		// Initialize args
		$args = \wp_parse_args(
			$args,
			[
				'title' => '',
				'type'  => '',
			]
		);

		// Use current user's ID if none specified
		if ( ! $user_id ) {
			$user_id = \get_current_user_id();
		}

		$point_slug = $this->slug;

		$before_points = (float) \get_user_meta( $user_id, $point_slug, true );
		$after_points  = $points;
		$point_changed = $after_points - $before_points;

		\update_user_meta( $user_id, $point_slug, $points );
		$args['new_balance']   = $points;
		$args['point_changed'] = $point_changed;

		\do_action( 'user_' . $point_slug . '_points_updated', $user_id, $args, $points, $point_slug );

		return (float) $points;
	}

	/**
	 * 扣多少點
	 *
	 * @param int $user_id - user id
	 * @param array $args - args
	 * @param float $points - points
	 *
	 * @return float updated points value
	 */
	public function deduct_points_to_user( int $user_id = 0, array $args = array(), float $points = 0 ): float {
		// If points are positive, turn them to negative
		if ( $points > 0 ) {
			$points *= - 1;
		}

		// Use current user's ID if none specified
		if ( ! $user_id ) {
			$user_id = \get_current_user_id();
		}
		$point_slug     = $this->slug;
		$current_points = (float) \get_user_meta( $user_id, $point_slug, true );
		$points         = $current_points + $points;

		return $this->update_user_points( $user_id, $args, $points );
	}
}
