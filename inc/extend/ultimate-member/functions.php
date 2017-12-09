<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//question
function um_dwqa_question_content() {
	add_filter( 'dwqa_prepare_archive_posts', 'um_dwqa_question_filter_query', 12 );
	remove_action( 'dwqa_before_questions_archive', 'dwqa_archive_question_filter_layout', 12 );
	// include(DWQA_DIR .'templates/bp-archive-question.php');
	global $dwqa;
	$dwqa->template->load_template( 'bp-archive', 'question' );
}

function um_dwqa_question_filter_query( $query ) {
	$current_user_id = um_profile_id();
	$query['author'] = $current_user_id;

	return $query;
}

//answer
function um_dwqa_answer_content() {
	add_filter( 'dwqa_prepare_archive_posts', 'um_dwqa_answer_filter_query', 12 );
	remove_action( 'dwqa_before_questions_archive', 'dwqa_archive_question_filter_layout', 12 );
	// include(DWQA_DIR .'templates/bp-archive-question.php');	
	global $dwqa;
	$dwqa->template->load_template( 'bp-archive', 'question' );
}

function um_dwqa_answer_filter_query( $query ) {
	$current_user_id = um_profile_id();
	$post__in        = array();

	$array              = $query;
	$array['post_type'] = 'dwqa-answer';
	$array['author']    = $current_user_id;

	// add_filter( 'posts_groupby', 'bp_dwqa_answers_groupby' );
	// use this function to fill per page
	while ( count( $post__in ) < $query['posts_per_page'] ) {
		$array['post__not_in '] = $post__in;
		$results                = new WP_Query( $array );

		if ( $results->post_count > 0 ) {
			foreach ( $results->posts as $result ) {
				$post__in[] = $result->post_parent;
			}
		} else {
			break;
		}
	}

	$query['post__in'] = $post__in;
	$query['orderby']  = 'post__in';

	return $query;
}
