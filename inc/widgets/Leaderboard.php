<?php

class DWQA_Leaderboard_Widget extends WP_Widget {
	public function __construct() {
		$widget_ops = array(
			'class_name'  => 'dwqa_leaderboard',
			'description' => 'Leaderboard for DWQA'
		);
		parent::__construct( 'dwqa_leaderboard', __( 'DWQA Leaderboard', 'dwqa' ), $widget_ops );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
	}

	public function widget( $args, $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Leaderboard', 'dwqa' );
		$limit = isset( $instance['limit'] ) && ! empty( $instance['limit'] ) ? $instance['limit'] : 10;
		$time  = isset( $instance['time'] ) ? $instance['time'] : 'all_the_time';

		$users = method_exists( __CLASS__, $time ) ? $this->{$time}( $limit ) : $this->query( $limit );
		extract( $args );
		echo $before_widget;
		echo $title = $before_title . apply_filters( 'widget_title', $title ) . $after_title;
		if ( ! empty( $users ) ) :
			$i = 1;
			?>
            <ul class="dwqa-leaderboard">
				<?php foreach ( $users as $user ) : ?>
                    <li>
                        <div class="dwqa-user-block">
                            <div class="dwqa-user-avatar">
								<?php echo get_avatar( $user['user_id'], 64 ) ?>
                            </div>
                            <div class="dwqa-user-content">
							<span class="dwqa-user-header">
								<span class="dwqa-rank-<?php echo $i ?>"><?php echo $i ?>.</span>
								<a href="<?php echo dwqa_get_author_link( $user['user_id'] ) ?>"><?php the_author_meta( 'display_name', $user['user_id'] ) ?></a>
							</span>
                                <span class="quesiton-count"><?php echo $this->count_question( $user['user_id'], $time ) ?><?php _e( 'questions', 'dwqa' ) ?></span>
                                <span class="answer-count"><?php echo $user['answer'] ?><?php _e( 'answers', 'dwqa' ) ?></span>
                            </div>
                        </div>
                    </li>
					<?php $i ++; ?>
				<?php endforeach; ?>
            </ul>
		<?php
		endif;
		echo $after_widget;
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Leaderboard', 'dwqa' );
		$limit = isset( $instance['limit'] ) && ! empty( $instance['limit'] ) ? $instance['limit'] : 10;
		$time  = isset( $instance['time'] ) ? $instance['time'] : 'all_the_time';
		?>
        <p>
            <label>
				<?php _e( 'Title', 'dwqa' ); ?>
                <input class="widefat" name="<?php echo $this->get_field_name( 'title' ) ?>"
                       id="<?php echo $this->get_field_id( 'title' ) ?>" value="<?php echo $title ?>">
            </label>
        </p>

        <p>
            <label>
				<?php _e( 'Number of top users/members', 'dwqa' ); ?>
                <input class="widefat" name="<?php echo $this->get_field_name( 'limit' ) ?>"
                       id="<?php echo $this->get_field_id( 'limit' ) ?>" value="<?php echo $limit ?>">
            </label>
        </p>

        <p>
            <label>
				<?php _e( 'Time', 'dwqa' ); ?>
                <select id="<?php echo $this->get_field_id( 'time' ) ?>"
                        name="<?php echo $this->get_field_name( 'time' ) ?>">
                    <option <?php selected( $time, 'all_the_time' ) ?>
                            value="all_the_time"><?php _e( 'All the time', 'dwqa' ); ?></option>
                    <option <?php selected( $time, 'this_month' ); ?>
                            value="this_month"><?php _e( 'This month', 'dwqa' ); ?></option>
                    <option <?php selected( $time, 'last_month' ); ?>
                            value="last_month"><?php _e( 'Last month', 'dwqa' ); ?></option>
                    <option <?php selected( $time, 'this_week' ); ?>
                            value="this_week"><?php _e( 'This week', 'dwqa' ); ?></option>
                    <option <?php selected( $time, 'last_week' ); ?>
                            value="last_week"><?php _e( 'Last week', 'dwqa' ); ?></option>
                </select>
            </label>
        </p>
		<?php
	}

	public function update( $new, $old ) {
		return $new;
	}

	public function query( $number = 10, $from = false, $to = false ) {
		global $wpdb;

		$query = "SELECT post_author as `user_id`, count(*) as `answer`
					FROM `{$wpdb->prefix}posts`
					WHERE post_type = 'dwqa-answer'
						AND post_status = 'publish'
						AND post_author <> 0";
		if ( $from ) {
			$from  = date( 'Y-m-d h:i:s', $from );
			$query .= " AND `{$wpdb->prefix}posts`.post_date > '{$from}'";
		}
		if ( $to ) {
			$to    = date( 'Y-m-d h:i:s', $to );
			$query .= " AND `{$wpdb->prefix}posts`.post_date < '{$to}'";
		}

		$prefix = '-all';
		if ( $from && $to ) {
			$prefix = '-' . ( $from - $to );
		}

		$query .= " GROUP BY `user_id`
					ORDER BY `answer` DESC LIMIT 0,{$number}";
		$users = wp_cache_get( 'dwqa-most-answered' . $prefix );
		if ( false == $users ) {
			$users = $wpdb->get_results( $query, ARRAY_A );
			wp_cache_set( 'dwqa-most-answered', $users );
		}

		return $users;
	}

	public function count_question( $user_id, $type ) {
		global $wpdb;

		$from = false;
		$to   = false;

		if ( 'this_month' == $type ) {
			$from = strtotime( 'first day of this month' );
			$to   = strtotime( 'last day of this month' );
		} else if ( 'last_month' == $type ) {
			$from = strtotime( 'first day of last month' );
			$to   = strtotime( 'last day of last month' );
		} else if ( 'this_week' == $type ) {
			$from = strtotime( 'monday this week' );
			$to   = strtotime( '+1 week', $from );
		} else if ( 'last_week' == $type ) {
			$from = strtotime( 'monday last week' );
			$to   = strtotime( '+1 week', $from );
		}

		$prefix = '-all';
		if ( $from && $to ) {
			$prefix = '-' . ( $from - $to );
		}

		$query = "SELECT count(*) FROM {$wpdb->posts} WHERE post_author = {$user_id} AND post_type = 'dwqa-question'";

		if ( $from ) {
			$from  = date( 'Y-m-d h:i:s', $from );
			$query .= " AND post_date > '{$from}'";
		}

		if ( $to ) {
			$to    = date( 'Y-m-d h:i:s', $to );
			$query .= " AND post_date < '{$to}'";
		}

		$users = wp_cache_get( 'dwqa-most-answered-question-count' . $prefix );
		if ( $users == false ) {
			$users = $wpdb->get_var( $query );
			wp_cache_set( 'dwqa-most-answered', $users );
		}

		return $users;
	}

	public function this_month( $number = 10 ) {
		$from = strtotime( 'first day of this month' );
		$to   = strtotime( 'last day of this month' );

		return $this->query( $number, $from, $to );
	}

	public function last_month( $number = 10 ) {
		$from = strtotime( 'first day of last month' );
		$to   = strtotime( 'last day of last month' );

		return $this->query( $number, $from, $to );
	}

	public function this_week( $number = 10 ) {
		$from = strtotime( 'monday this week' );
		$to   = strtotime( '+1 week', $from );

		return $this->query( $number, $from, $to );
	}

	public function last_week( $number = 10 ) {
		$from = strtotime( 'monday last week' );
		$to   = strtotime( '+1 week', $from );

		return $this->query( $number, $from, $to );
	}

	public function enqueue_script() {
		wp_enqueue_style( 'dwqa_leaderboard', DWQA_URI . 'templates/assets/css/leaderboard.css' );
	}
}
