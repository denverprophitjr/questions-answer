<?php

class DWQA_Rewrite {
	public function __construct() {
		add_action( 'after_switch_theme', 'flush_rewrite_rules' );

		add_filter( 'post_type_link', array( $this, 'dwqa_add_category_slug' ), 10, 4 );
		add_action( 'init', array( $this, 'custom_rewrite_basic' ) );

	}

	function custom_rewrite_basic() {
		global $dwqa_general_settings;
		if ( ! $dwqa_general_settings ) {
			$dwqa_general_settings = get_option( 'dwqa_options' );
		}
		if ( isset( $dwqa_general_settings['question-slug-category-rewrite'] ) && $dwqa_general_settings['question-slug-category-rewrite'] ) {
			$slug = isset( $dwqa_general_settings['question-rewrite'] ) ? $dwqa_general_settings['question-rewrite'] : 'question';
			add_rewrite_rule( $slug . '/(.*)?/(.*)?$', 'index.php?post_type=dwqa-question&name=$matches[2]&dwqa-question=$matches[2]', 'top' );
		}
	}

	public function dwqa_add_category_slug( $post_link, $post, $leavename, $sample ) {
		global $dwqa_general_settings;
		if ( $post->post_type == 'dwqa-question' && isset( $dwqa_general_settings['question-slug-category-rewrite'] ) && $dwqa_general_settings['question-slug-category-rewrite'] ) {
			global $dwqa;
			global $wp_rewrite;

			$post_link = $wp_rewrite->get_extra_permastruct( $post->post_type );

			$slug = $post->post_name;

			$draft_or_pending = get_post_status( $post->ID ) && in_array( get_post_status( $post->ID ), array(
					'draft',
					'pending',
					'auto-draft',
					'future'
				) );

			$post_type = get_post_type_object( $post->post_type );

			if ( $post_type->hierarchical ) {
				$slug = get_page_uri( $post->ID );
			}

			$category_slug   = $dwqa->question->get_slug() . '_category';
			$category_detail = get_the_terms( $post->ID, $category_slug );

			if ( isset( $category_detail[0] ) && ! empty( $category_detail[0] ) ) {
				$slug = $category_detail[0]->slug . '/' . $slug;
			}

			if ( ! empty( $post_link ) && ( ! $draft_or_pending || $sample ) ) {
				if ( ! $leavename ) {
					$post_link = str_replace( "%$post->post_type%", $slug, $post_link );
				}
				$post_link = home_url( user_trailingslashit( $post_link ) );
			} else {
				if ( $post_type->query_var && ( isset( $post->post_status ) && ! $draft_or_pending ) ) {
					$post_link = add_query_arg( $post_type->query_var, $slug, '' );
				} else {
					$post_link = add_query_arg( array( 'post_type' => $post->post_type, 'p' => $post->ID ), '' );
				}
				$post_link = home_url( $post_link );
			}
		}

		return $post_link;
	}

	function update_term_rewrite_rules() {
		//add rewrite for question taxonomy
		global $wp_rewrite;
		$options = get_option( 'dwqa_options' );

		$page_id            = $options['pages']['archive-question'];
		$question_list_page = get_page( $page_id );
		$rewrite_category   = isset( $options['question-category-rewrite'] ) ? sanitize_title( $options['question-category-rewrite'] ) : 'question-category';
		$rewrite_tag        = isset( $options['question-tag-rewrite'] ) ? sanitize_title( $options['question-tag-rewrite'] ) : 'question-tag';

		if ( $question_list_page ) {
			$dwqa_rewrite_rules = array(
				'^' . $question_list_page->post_name . '/' . $rewrite_category . '/([^/]*)' => 'index.php?page_id=' . $page_id . '&taxonomy=dwqa-question_category&dwqa-question_category=$matches[1]',
				'^' . $question_list_page->post_name . '/' . $rewrite_tag . '/([^/]*)'      => 'index.php?page_id=' . $page_id . '&taxonomy=dwqa-question_tag&dwqa-question_tag=$matches[1]',
			);
			foreach ( $dwqa_rewrite_rules as $regex => $redirect ) {
				add_rewrite_rule( $regex, $redirect, 'top' );
			}
			// Add permastruct for pretty link
			add_permastruct( 'dwqa-question_category', "{$question_list_page->post_name}/{$rewrite_category}/%dwqa-question_category%", array( 'with_front' => false ) );
			add_permastruct( 'dwqa-question_tag', "{$question_list_page->post_name}/{$rewrite_tag}/%dwqa-question_tag%", array( 'with_front' => false ) );
		}
	}
}
?>
