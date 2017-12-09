<?php

class DWQA_Log {
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );

		add_action( 'delete_post', array( $this, 'auto_clear_question_log' ), 10, 1 );
	}

	public function register_post_type() {
		$log_args = array(
			'labels'              => array( 'name' => __( 'Logs', 'dwqa' ) ),
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'supports'            => array( 'title', 'editor' ),
			'can_export'          => true,
		);

		register_post_type( 'dwqa_log', $log_args );
		register_taxonomy( 'dwqa_log_type', 'dwqa_log', array( 'public' => false ) );
	}

	public function add( $title = '', $message = '', $parent = 0, $type = null ) {
		$data = array(
			'post_content' => $message,
			'post_title'   => $title,
			'post_parent'  => $parent,
			'log_type'     => $type,
		);

		return $this->insert_log( $data );
	}

	public function get_logs( $parent_id = 0, $type = 0, $paged = null ) {
		return $this->get_all_logs( array( 'post_parent' => $parent_id, 'log_type' => $type, 'paged' => $paged ) );
	}

	public function insert_log( $data = array(), $metadata = array() ) {
		$args = array(
			'post_title'   => '',
			'post_content' => '',
			'post_status'  => 'publish',
			'post_parent'  => 0,
			'post_type'    => 'dwqa_log',
			'log_type'     => ''
		);

		$args = wp_parse_args( $data, $args );

		$log_id = wp_insert_post( $args );

		if ( ! $log_id || is_wp_error( $log_id ) ) {
			return $log_id;
		}

		if ( $args['log_type'] ) {
			if ( term_exists( $args['log_type'] ) ) {
				wp_set_object_terms( $log_id, $args['log_type'], 'dwqa_log_type', false );
			} else {
				wp_insert_term( $args['log_type'], 'dwqa_log_type' );
				wp_set_object_terms( $log_id, $args['log_type'], 'dwqa_log_type', false );
			}
		}

		if ( ! empty( $metadata ) ) {
			foreach ( (array) $metadata as $key => $value ) {
				update_post_meta( $log_in, '_dwqa_log_' . sanitize_key( $key ), $value );
			}
		}

		return $log_id;
	}

	public function update_log( $data = array(), $metadata = array() ) {
		$args = array(
			'post_type'   => 'dwqa_log',
			'post_status' => 'publish',
			'post_parent' => 0
		);

		$args = wp_parse_args( $data, $args );

		$log_id = wp_update_post( $args );

		if ( ! $log_id || is_wp_error( $log_id ) ) {
			return $log_id;
		}

		if ( ! empty( $metadata ) ) {
			foreach ( (array) $metadata as $key => $value ) {
				update_post_meta( $log_in, '_dwqa_log_' . sanitize_key( $key ), $value );
			}
		}

		return $log_id;
	}

	public function get_all_logs( $data = array() ) {
		$args = array(
			'post_type'      => 'dwqa_log',
			'posts_per_page' => 20,
			'post_status'    => 'publish',
			'paged'          => get_query_var( 'paged' ),
			'log_type'       => false,
		);

		$args = wp_parse_args( $data, $args );

		if ( $args['log_type'] ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'dwqa_log_type',
				'field'    => 'slug',
				'terms'    => $args['log_type']
			);
		}

		$logs = get_post( $args );

		if ( $logs ) {
			return $logs;
		}

		return false;
	}

	public function delete_logs( $parent_id = 0, $type = null, $meta_query = null ) {
		$args = array(
			'post_parent'            => $parent_id,
			'post_type'              => 'dwqa_log',
			'nopaging'               => true,
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			// all the fields below make query faster
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		if ( ! empty( $type ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'dwqa_log_type',
				'field'    => 'slug',
				'terms'    => $type,
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$logs = get_posts( $args );

		if ( $logs ) {
			foreach ( $logs as $log ) {
				wp_delete_post( $log, true );
			}
		}
	}

	public function auto_clear_question_log( $parent_id = 0 ) {
		$question_id = absint( $parent_id );
		if ( 'dwqa-answer' == get_post_type( $parent_id ) ) {
			$question_id = get_post_meta( $parent_id, '_question', true );
		}

		$this->delete_logs( $question_id );
	}
}
