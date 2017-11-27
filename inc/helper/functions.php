<?php
/** 
 * This file was used to include all functions which i can't classify, just use those for support my work
 */

/** 
 * Array
 */
function dwqa_array_insert( &$array, $element, $position = null ) {
	if ( is_array( $element ) ) {
		$part = $element;
	} else {
		$part = array( $position => $element );
	}

	$len = count( $array );

	$firsthalf  = array_slice( $array, 0, $len / 2 );
	$secondhalf = array_slice( $array, $len / 2 );

	$array = array_merge( $firsthalf, $part, $secondhalf );
	return $array;
}

if ( ! function_exists( 'dw_strip_email_to_display' ) ) { 
	/**
	 * Strip email for display in front end
	 *
	 * @param  string $text name
	 * @param  boolean $echo Display or just return
	 *
	 * @return string        New text that was stripped
	 */
	function dw_strip_email_to_display( $text, $echo = false ) {
		preg_match( '/( [^\@]* )\@( .* )/i', $text, $matches );
		if ( ! empty( $matches ) ) {
			$text = $matches[1] . '@...';
		}
		if ( $echo ) {
			echo $text;
		}
		return $text;
	}
}  

// CAPTCHA
function dwqa_valid_captcha( $type ) {
	global $dwqa_general_settings;

	if ( 'comment' == $type && ! dwqa_is_captcha_enable_in_comment() ) {
		return true;
	}

	if ( 'question' == $type && ! dwqa_is_captcha_enable_in_submit_question() ) {
		return true;
	}

	if ( 'single-question' == $type && ! dwqa_is_captcha_enable_in_single_question() ) {
		return true;
	}
	
	return apply_filters( 'dwqa_valid_captcha', false );
}

add_filter( 'dwqa_valid_captcha', 'dwqa_captcha_check' );
function dwqa_captcha_check( $res ) {
	global $dwqa_general_settings;
	$type_selected = isset( $dwqa_general_settings['captcha-type'] ) ? $dwqa_general_settings['captcha-type'] : 'default';

	$is_old_version = $type_selected == 'google-recaptcha' ? true : false;
	// math captcha
	if ( $type_selected == 'default' || $is_old_version ) {
		$number_1 = isset( $_POST['dwqa-captcha-number-1'] ) ? (int) $_POST['dwqa-captcha-number-1'] : 0;
		$number_2 = isset( $_POST['dwqa-captcha-number-2'] ) ? (int) $_POST['dwqa-captcha-number-2'] : 0;
		$result   = isset( $_POST['dwqa-captcha-result'] ) ? (int) $_POST['dwqa-captcha-result'] : 0;

		if ( ( $number_1 + $number_2 ) === $result ) {
			return true;
		}

		return false;
	}

	// Google reCaptcha v2
	if ( $type_selected == 'google-captcha-v2' ) {
		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			return false;
		}

		$private_key = isset( $dwqa_general_settings['captcha-google-private-key'] ) ? $dwqa_general_settings['captcha-google-private-key'] : '';

		$url = 'https://www.google.com/recaptcha/api/siteverify';

		$args = array(
			'method' => 'POST',
			'body'   => array(
				'secret'   => $private_key,
				'response' => $_POST['g-recaptcha-response'],
				'remoteip' => $_SERVER['REMOTE_ADDR']
			),
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body );

		if ( $body->success ) {
			return true;
		}
	}

	// FunCaptcha
	if ( $type_selected == 'funcaptcha' ) {
		if ( empty( $_POST['fc-token'] ) ) {
			return false;
		}

		$private_key = isset( $dwqa_general_settings['funcaptcha-private-key'] ) ? $dwqa_general_settings['funcaptcha-private-key'] : '';
		$fc_api_url  = "https://funcaptcha.com/fc/v/?private_key=" . $private_key . "&session_token=" . $_POST['fc-token'] . "&simple_mode=1";

		if ( file_get_contents( $fc_api_url ) === "1" ) {
			return true;
		}
	}

	return $res;
}

/**
 * Get tags list of question
 *
 * @param int $quetion id of question
 * @param bool $echo
 * @return string
 * @since 1.4.0
*/
function dwqa_get_tag_list( $question = false, $echo = false ) {
	if ( ! $question ) {
		$question = get_the_ID();
	}

	$terms = wp_get_post_terms( $question, 'dwqa-question_tag' );
	$lists = array();
	if ( $terms ) {
		foreach ( $terms as $term ) {
			$lists[] = $term->name;
		}
	}

	if ( empty( $lists ) ) {
		$lists = '';
	} else {
		$lists = implode( ',', $lists );
	}

	if ( $echo ) {
		echo $lists;
	}

	return $lists;
}


function dwqa_is_front_page() {
	global $dwqa_general_settings;

	if ( ! $dwqa_general_settings ) {
		$dwqa_general_settings = get_option( 'dwqa_options' );
	}

	if ( ! isset( $dwqa_general_settings['pages']['archive-question'] ) ) {
		return false;
	}

	$page_on_front = get_option( 'page_on_front' );

	if ( $page_on_front === $dwqa_general_settings['pages']['archive-question'] ) {
		return true;
	}

	return false;
}

function dwqa_has_question( $args = array() ) {
	global $wp_query;

	return $wp_query->dwqa_questions->have_posts();
}

function dwqa_the_question() {
	global $wp_query;

	return $wp_query->dwqa_questions->the_post();
}

function dwqa_has_question_stickies() {
	global $wp_query;

	return isset( $wp_query->dwqa_question_stickies ) ? $wp_query->dwqa_question_stickies->have_posts() : false;
}

function dwqa_the_sticky() {
	global $wp_query;

	return $wp_query->dwqa_question_stickies->the_post();
}

function dwqa_has_answers() {
	global $wp_query;

	return isset( $wp_query->dwqa_answers ) ? $wp_query->dwqa_answers->have_posts() : false;
}

function dwqa_the_answers() {
	global $wp_query;

	return $wp_query->dwqa_answers->the_post();
}

function dwqa_get_answer_count( $question_id = false ) {

	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

	$answer_count = get_post_meta( $question_id, '_dwqa_answers_count', true );

	if ( current_user_can( 'edit_posts' ) ) {
		return $answer_count;
	} else {
		$answer_private = get_post_meta( $question_id, 'dwqa_answers_private_count', true );

		if ( empty( $answer_private ) ) {
			global $wp_query;
			$args = array(
				'post_type'              => 'dwqa-answer',
				'post_status'            => 'private',
				'meta_query'             => array(
					'key'     => '_question',
					'value'   => array( $question_id ),
					'compare' => 'IN'
				),
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'fields'                 => 'ids'
			);

			$private_answer = new WP_Query( $args );

			update_post_meta( $question_id, 'dwqa_answers_private_count', count( $private_answer ) );
			$answer_private = count( $private_answer );
		}

		return (int) $answer_count - (int) $answer_private;
	}
}

function dwqa_is_ask_form() {
	global $dwqa_general_settings;
	if ( ! isset( $dwqa_general_settings['pages']['submit-question'] ) ) {
		return false;
	}

	return is_page( $dwqa_general_settings['pages']['submit-question'] );
}

function dwqa_is_archive_question() {
	global $dwqa_general_settings;
	if ( ! isset( $dwqa_general_settings['pages']['archive-question'] ) ) {
		return false;
	}
	
	return is_page( $dwqa_general_settings['pages']['archive-question'] );
}

function dwqa_question_status( $question = false ) {
	if ( ! $question ) {
		$question = get_the_ID();
	}

	$status = get_post_meta( $question, '_dwqa_status', true );

	if ( 'close' == $status ) {
		$status = 'closed';
	}

	return $status;
}

function dwqa_current_filter() {
	return isset( $_GET['filter'] ) && ! empty( $_GET['filter'] ) ? $_GET['filter'] : 'all';
}

function dwqa_get_ask_link() {
	global $dwqa_general_settings;

	return get_permalink( $dwqa_general_settings['pages']['submit-question'] );
}

function dwqa_get_question_link( $post_id ) {
	if ( 'dwqa-answer' == get_post_type( $post_id ) ) {
		$post_id = dwqa_get_question_from_answer_id( $post_id );
	}

	return get_permalink( $post_id );
}

function dwqa_markdown_to_html( $content = '' ) {
	if ( ! class_exists( 'Parsedown' ) ) {
		include( DWQA_DIR . 'lib/Parsedown.php' );
	}

	$parsedown = new Parsedown();

	return $parsedown->text( $content );
}

function dwqa_html_to_markdown( $content = '' ) {
	if ( ! class_exists( 'HTML_To_Markdown' ) ) {
		include( DWQA_DIR . 'lib/to-markdown.php' );
	}

	$to_markdown = new HTML_To_Markdown();

	return $to_markdown->convert( $content );
}

// this function run when active markdown editor
function dwqa_new_paragraph( $content ) {
	global $dwqa_options;
	if ( isset( $dwqa_options['markdown-editor'] ) && $dwqa_options['markdown-editor'] ) {
		$explode = explode( "\n", $content );
		$result  = '';
		foreach ( $explode as $str ) {
			$result .= '<p>' . $str . '</p>';
		}

		return $result;
	}

	return $content;
}

add_action( 'wp_loaded', 'dwqa_init_action_and_filter' );
function dwqa_init_action_and_filter() {
	global $dwqa_options;
	if ( isset( $dwqa_options['markdown-editor'] ) && $dwqa_options['markdown-editor'] ) {
		// Answer
		add_filter( 'dwqa_prepare_answer_content', 'dwqa_markdown_to_html', 1 );
		add_filter( 'dwqa_prepare_edit_answer_content', 'dwqa_markdown_to_html', 1 );
		add_filter( 'dwqa_answer_get_edit_content', 'dwqa_html_to_markdown', 1 );
		add_filter( 'dwqa_prepare_answer_content', 'dwqa_new_paragraph' );

		// Question
		add_filter( 'dwqa_prepare_question_content', 'dwqa_markdown_to_html', 1 );
		add_filter( 'dwqa_prepare_edit_question_content', 'dwqa_markdown_to_html', 1 );
		add_filter( 'dwqa_question_get_edit_content', 'dwqa_html_to_markdown', 1 );
		add_filter( 'dwqa_prepare_question_content', 'dwqa_new_paragraph' );
	}
}

function dwqa_get_latest_activity_text( $context ) {
	$latest_activity_texts = apply_filters( 'dwqa_get_latest_activity_text', array(
		'answered'  => __( 'answered', 'dwqa' ),
		'commented' => __( 'commented', 'dwqa' ),
	) );

	foreach ( $latest_activity_texts as $key => $text ) {
		if ( $context == $key ) {
			return $text;
		}
	}

	return $context;
}

function dwqa_get_total_vote( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	$count = wp_cache_get( 'dwqa_get_total_vote_' . $question_id );
	if ( ! $count ) {
		$count = (int) dwqa_vote_count( $question_id );

		$args = array(
			'post_type'              => 'dwqa-answer',
			'post_status'            => 'publish',
			'posts_per_page'         => - 1,
			'fields'                 => 'ids',
			'meta_query'             => array(
				array(
					'key'     => '_question',
					'value'   => $question_id,
					'compare' => '='
				)
			),
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'no_found_rows'          => true
		);

		$answers = get_posts( $args );

		if ( sizeof( $answers ) > 0 ) {
			foreach ( $answers as $answer_id ) {
				$count += dwqa_vote_count( $answer_id );
			}
		}

		wp_cache_set( 'dwqa_get_total_vote_' . $question_id, $count );
	}

	return $count;
}

function dwqa_register_page() {
	global $dwqa_general_settings;

	$register_page = isset( $dwqa_general_settings['pages']['register-page'] ) ? $dwqa_general_settings['pages']['register-page'] : 0;

	return $register_page;
}

add_filter( 'the_content', 'dwqa_disable_do_shortcode', 10 );
function dwqa_disable_do_shortcode( $content ) {
	global $post;
	if ( isset( $post ) && ( 'dwqa-question' == $post->post_type || 'dwqa-answer' == $post->post_type ) ) {
		remove_filter( 'the_content', 'do_shortcode', 11 );
	}

	return $content;
}

?>
