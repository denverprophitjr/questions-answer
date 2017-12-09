<?php

class DWQA_Widget_Categories_List extends WP_Widget {
	public function __construct() {
		$widget_ops = array( 'classname'   => 'widget_categories dwqa_widget dwqa_widget_categories',
		                     'description' => __( 'Show a list of categories or tags.', 'dwqa-widget' )
		);
		parent::__construct( 'dwqa-categories', __( 'DWQA Categories/Tags', 'dwqa-widget' ), $widget_ops );
	}

	public function widget( $args, $instance ) {
		$title               = isset( $instance['title'] ) ? $instance['title'] : __( 'DWQA Categories', 'dwqa-widget' );
		$taxonomy            = isset( $instance['taxonomy'] ) ? $instance['taxonomy'] : 'dwqa-question_category';
		$show_count          = isset( $instance['show_count'] ) ? $instance['show_count'] : false;
		$hide_empty          = isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : false;
		$dropdown            = isset( $instance['dropdown'] ) ? $instance['dropdown'] : false;
		$query               = array();
		$query['hide_empty'] = 'on' == $hide_empty ? true : false;
		$question_categories = get_terms( $taxonomy, $query );
		extract( $args );

		echo $before_widget;
		echo $before_title;
		echo $title;
		echo $after_title;
		if ( $question_categories && ! is_wp_error( $question_categories ) && is_array( $question_categories ) ) {
			if ( ! $dropdown ) {
				echo '<ul>';
				foreach ( $question_categories as $cat ) {
					if ( isset( $cat->term_id ) ) {
						echo '<li class="cat-item cat-item-' . $cat->term_id . '"><a href="' . get_term_link( $cat->term_id ) . '" title="' . esc_attr( $cat->name ) . '">';
						echo esc_attr( $cat->name );
						echo '</a>&#32;';
						if ( $show_count ) {
							echo '<span class="badge">&#40;' . intval( $cat->count ) . '&#41;</span>';
						}
						echo '</li>';
					}
				}
				echo '</ul>';
			} else {
				echo '<select onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">';
				foreach ( $question_categories as $cat ) {
					if ( isset( $cat->term_id ) ) {
						echo '<option value="' . get_term_link( $cat->term_id ) . '">' . $cat->name;
						if ( $show_count ) {
							echo '&#32;&#40;' . intval( $cat->count ) . '&#41;';
						}
						echo '</option>';
					}
				}
				echo '</select>';
			}
		}
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	public function form( $instance ) {
		$title      = isset( $instance['title'] ) ? $instance['title'] : '';
		$taxonomy   = isset( $instance['taxonomy'] ) ? $instance['taxonomy'] : 'dwqa-question_category';
		$show_count = isset( $instance['show_count'] ) ? $instance['show_count'] : false;
		$hide_empty = isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : false;
		$dropdown   = isset( $instance['dropdown'] ) ? $instance['dropdown'] : false;
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ) ?>">
				<?php _e( 'Title', 'dwqa-widget' ) ?>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ) ?>"
                       name="<?php echo $this->get_field_name( 'title' ) ?>" value="<?php echo $title ?>">
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'taxonomy' ) ?>">
				<?php _e( 'Taxonomy', 'dwqa' ) ?>
                <select class="widefat" id="<?php echo $this->get_field_id( 'taxonomy' ) ?>"
                        name="<?php echo $this->get_field_name( 'taxonomy' ) ?>">
                    <option <?php selected( $taxonomy, 'dwqa-question_category' ) ?>
                            value="dwqa-question_category"><?php _e( 'Categories', 'dwqa' ) ?></option>
                    <option <?php selected( $taxonomy, 'dwqa-question_tag' ) ?>
                            value="dwqa-question_tag"><?php _e( 'Tags', 'dwqa' ) ?></option>
                </select>
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'show_count' ) ?>">
                <input type="checkbox" id="<?php echo $this->get_field_id( 'show_count' ) ?>"
                       name="<?php echo $this->get_field_name( 'show_count' ) ?>" <?php checked( $show_count, 'on' ) ?>>
				<?php _e( 'Show count', 'dwqa' ); ?>
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'hide_empty' ) ?>">
                <input type="checkbox" id="<?php echo $this->get_field_id( 'hide_empty' ) ?>"
                       name="<?php echo $this->get_field_name( 'hide_empty' ) ?>" <?php checked( $hide_empty, 'on' ) ?>>
				<?php _e( 'Hide Empty Categories/Tags', 'dwqa' ) ?>
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'dropdown' ) ?>">
                <input type="checkbox" id="<?php echo $this->get_field_id( 'dropdown' ) ?>"
                       name="<?php echo $this->get_field_name( 'dropdown' ) ?>" <?php checked( $dropdown, 'on' ) ?>>
				<?php _e( 'Display as dropdown', 'dwqa' ) ?>
            </label>
        </p>
		<?php
	}
}
