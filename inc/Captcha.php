<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//update comment captcha
class DWQA_Captcha {
	private $type_selected = 'default';

	public function __construct() {
		global $dwqa_general_settings;

		$this->type_selected = isset( $dwqa_general_settings['captcha-type'] ) ? $dwqa_general_settings['captcha-type'] : 'default';
		add_action( 'wp_enqueue_scripts', array( $this, 'dwqa_captcha_scripts' ) );

		// if(isset( $dwqa_general_settings['captcha-in-question'] ) && $dwqa_general_settings['captcha-in-question']){
		// 	add_action('dwqa_show_captcha_question', array($this, 'dwqa_show_captcha'));
		// }	

		if ( isset( $dwqa_general_settings['captcha-in-comment-single-question'] ) && $dwqa_general_settings['captcha-in-comment-single-question'] ) {
			add_action( 'dwqa_show_captcha_comment', array( $this, 'dwqa_show_captcha_comment' ) );
		}
	}

	public function dwqa_show_captcha_comment( $post_id ) {
		if ( $this->type_selected == 'google-captcha-v2' ) {
			echo '<div id="dwqa-comment-gcaptchav2-' . $post_id . '" class="dwqa-comment-gcaptchav2" data-dwqa-id="dwqa-comment-gcaptchav2-' . $post_id . '"></div>';
		}
	}

	public function dwqa_captcha_scripts() {
		global $dwqa_general_settings;
		if ( $this->type_selected == 'google-captcha-v2' ) {

			$public_key = isset( $dwqa_general_settings['captcha-google-public-key'] ) ? $dwqa_general_settings['captcha-google-public-key'] : '';
			wp_register_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array() );
			$gcaptchav2 = array(
				'public_key' => $public_key,
			);
			wp_localize_script( 'google-recaptcha', 'dwqa_gcv2', $gcaptchav2 );

			// Enqueued script with localized data.
			wp_enqueue_script( 'google-recaptcha' );
		}
		if ( $this->type_selected == 'funcaptcha' ) {
			wp_enqueue_script( 'funcaptcha', 'https://funcaptcha.com/fc/api/', array() );
			echo '<script src="https://funcaptcha.com/fc/api/" async defer></script>';
		}
	}


}
