<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DWQA_Ultimate_Member' ) ) :

	class DWQA_Ultimate_Member {

		public function __construct() {

			$this->includes();
			/* First we need to extend main profile tabs */

			add_filter( 'um_profile_tabs', array( $this, 'add_dw_question_answer_tab' ), 1000 );

			/* Then we just have to add content to that tab using this action */

			add_action( 'um_profile_content_dwqatab_default', array(
				$this,
				'um_profile_content_dw_question_answer_default'
			), 10, 1 );
		}

		public function includes() {
			require_once( DWQA_DIR . 'inc/extend/ultimate-member/functions.php' );
		}

		public function add_dw_question_answer_tab( $tabs ) {

			$tabs['dwqatab'] = array(
				'name' => __( 'Question & Answer', 'dwqa' ),
				'icon' => 'um-faicon-question-circle',
			);

			return $tabs;
		}

		public function um_profile_content_dw_question_answer_default( $args ) {
			um_dwqa_question_content();
		}
	}
endif;
