<?php

class DWQA_Widgets_Related_Question extends WP_Widget {

	private $default_args = array();
	/**
	 * Constructor
	 *
	 * @return void
	 **/
	function __construct() {
		$this->default_args = array(
			'title'     => __( 'Related Questions', 'dwqa' ),
			'number'    => 5,
			'hide_date' => 0,
			'hide_user' => 0,
		);
		$widget_ops         = array( 'classname'   => 'dwqa-widget dwqa-related-questions',
		                             'description' => __( 'Show a list of questions that related to a question. Just show in single question page', 'dwqa' )
		);
		parent::__construct( 'dwqa-related-question', __( 'DWQA Related Questions', 'dwqa' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		$instance = wp_parse_args( $instance, $this->default_args );
		
		$post_type = get_post_type();
		if ( is_single() && ( $post_type == 'dwqa-question' || $post_type == 'dwqa-answer' ) ) {

			echo $before_widget;
			echo $before_title;
			echo $instance['title'];
			echo $after_title;
			echo '<div class="related-questions">';
			dwqa_related_question( false, $instance['number'], true, $instance['hide_user'], $instance['hide_date'] );
			echo '</div>';
			echo $after_widget;
		}
	}

	function update( $new_instance, $old_instance ) {

		// update logic goes here
		$updated_instance = $new_instance;
		return $updated_instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->default_args );
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Widget title', 'dwqa' ) ?></label>
            <input type="text" name="<?php echo $this->get_field_name( 'title' ) ?>"
                   id="<?php echo $this->get_field_id( 'title' ) ?>"
                   value="<?php echo sanitize_text_field( $instance['title'] ); ?>" class="widefat">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ) ?>"><?php _e( 'Number of posts', 'dwqa' ) ?></label>
            <input type="text" name="<?php echo $this->get_field_name( 'number' ) ?>"
                   id="<?php echo $this->get_field_id( 'number' ) ?>"
                   value="<?php echo intval( $instance['number'] ); ?>" class="widefat">
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
