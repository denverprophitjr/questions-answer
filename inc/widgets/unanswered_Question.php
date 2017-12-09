<?php

class DWQA_Widgets_unanswered_Question extends WP_Widget {


	private $default_args = array();

	/**
	 * Constructor
	 *
	 * @return void
	 **/
	function __construct() {

		$this->default_args = array(
			'title'     => __( 'Unanswered Questions', 'dwqa' ),
			'number'    => 5,
			'hide_date' => 0,
			'hide_user' => 0,
		);

		$widget_ops = array( 'classname'   => 'dwqa-widget dwqa-unanswered-questions',
		                     'description' => __( 'Show a list of questions marked as unanswered.', 'dwqa' )
		);
		parent::__construct( 'dwqa-unanswered-question', __( 'DWQA Unanswered Questions', 'dwqa' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		$instance = wp_parse_args( $instance, $this->default_args );

		echo $before_widget;
		echo $before_title;
		echo $instance['title'];
		echo $after_title;
		$args      = array(
			'post_type'      => 'dwqa-question',
			'posts_per_page' => $instance['number'],
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_dwqa_status',
					'compare' => '=',
					'value'   => 'resolved',
				),
				array(
					'key'     => '_dwqa_status',
					'compare' => '=',
					'value'   => 'open',
				),
			),
		);
		$questions = new WP_Query( $args );
		if ( $questions->have_posts() ) {
			echo '<div class="dwqa-popular-questions">';
			echo '<ul>';
			while ( $questions->have_posts() ) {
				$questions->the_post();
				echo '<li><a href="' . get_permalink() . '" class="question-title">' . get_the_title() . '</a>';
				if ( ! $instance['hide_user'] ) {
					echo __( ' asked by', 'dwqa' ) . ' ' . get_the_author_posts_link();
				}
				if ( ! $instance['hide_date'] ) {
					echo ', ' . human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) . __( ' ago', 'dwqa' );
				}
				echo '</li>';
			}
			echo '</ul>';
			echo '</div>';
		}
		wp_reset_query();
		wp_reset_postdata();
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		// update logic goes here
		$updated_instance = $new_instance;

		return $updated_instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->default_args );
		?>
        <p><label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Widget title', 'dwqa' ) ?></label>
            <input type="text" name="<?php echo $this->get_field_name( 'title' ) ?>"
                   id="<?php echo $this->get_field_id( 'title' ) ?>" value="<?php echo $instance['title'] ?>"
                   class="widefat">
        </p>
        <p><label for="<?php echo $this->get_field_id( 'number' ) ?>"><?php _e( 'Number of posts', 'dwqa' ) ?></label>
            <input type="text" name="<?php echo $this->get_field_name( 'number' ) ?>"
                   id="<?php echo $this->get_field_id( 'number' ) ?>" value="<?php echo $instance['number'] ?>"
                   class="widefat">
        </p>
        <p>
            <input id="<?php echo $this->get_field_id( 'hide_user' ) ?>"
                   name="<?php echo $this->get_field_name( 'hide_user' ) ?>"
                   type="checkbox" <?php echo $instance['hide_user'] ? 'checked' : ''; ?>>&nbsp;<label
                    for="<?php echo $this->get_field_id( 'hide_user' ) ?>"><?php _e( 'Hide author', 'dwqa' ) ?></label>
        </p>
        <p>
            <input id="<?php echo $this->get_field_id( 'hide_date' ) ?>"
                   name="<?php echo $this->get_field_name( 'hide_date' ) ?>"
                   type="checkbox" <?php echo $instance['hide_date'] ? 'checked' : ''; ?>>&nbsp;<label
                    for="<?php echo $this->get_field_id( 'hide_date' ) ?>"><?php _e( 'Hide date', 'dwqa' ) ?></label>
        </p>
		<?php
	}
}

?>
