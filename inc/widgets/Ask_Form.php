<?php

class DWQA_Widget_Ask_Form extends WP_Widget {
	public function __construct() {
		$widget_ops = array( 'classname'   => 'dwqa_widget dwqa_widget_ask_form',
		                     'description' => __( 'Show ask question form in sidebar.', 'dwqa-widget' )
		);
		parent::__construct( 'dwqa-ask-form', __( 'DWQA Ask Form', 'dwqa-widget' ), $widget_ops );
	}

	public function widget( $args, $instance ) {
		$instance['title'] = isset( $instance['title'] ) ? $instance['title'] : '';
		extract( $args );

		echo $before_widget;
		echo $before_title;
		echo $instance['title'];
		echo $after_title;
		if ( has_shortcode( '', 'dwqa-submit-question-form' ) ) {
			echo do_shortcode( '[dwqa-submit-question-form]' );
		} else {
			global $dwqa;
			echo $dwqa->shortcode->submit_question_form_shortcode();
		}

		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ) ?>">
				<?php _e( 'Title', 'dwqa-widget' ) ?>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ) ?>"
                       name="<?php echo $this->get_field_name( 'title' ) ?>" value="<?php echo $title ?>">
            </label>
        </p>
		<?php
	}
}
