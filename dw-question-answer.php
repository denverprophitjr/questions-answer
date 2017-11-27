<?php
/**
 *  Plugin Name: DW Question Answer Pro
 *  Description: A WordPress plugin developed by DesignWall.com to build a complete Question & Answer system for your WordPress site like Quora, Stackoverflow, etc.
 *  Author: DesignWall
 *  Author URI: http://www.designwall.com
 *  Version: 1.1.2
 *  Text Domain: dwqa
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DW_Question_Answer' ) ) :

	class DW_Question_Answer {
		private $last_update = 190820170946; //last update time of the plugin

		public function __construct() {
			$this->define_constants();
			$this->includes();

			$this->dir = DWQA_DIR;
			$this->uri = DWQA_URI;

			$this->version    = '1.1.2';
			$this->db_version = '1.0.7';

			// load posttype
			$this->question = new DWQA_Posts_Question();
			$this->answer   = new DWQA_Posts_Answer();
			$this->comment  = new DWQA_Posts_Comment();

			$this->rewrite = new DWQA_Rewrite();

			$this->ajax          = new DWQA_Ajax();
			$this->handle        = new DWQA_Handle();
			$this->permission    = new DWQA_Permission();
			$this->status        = new DWQA_Status();
			$this->shortcode     = new DWQA_Shortcode();
			$this->template      = new DWQA_Template();
			$this->settings      = new DWQA_Settings();
			$this->editor        = new DWQA_Editor();
			$this->user          = new DWQA_User();
			$this->notifications = new DWQA_Notifications();

			$this->upload      = new DWQA_Upload();
			$this->akismet     = new DWQA_Akismet();
			$this->autoclosure = new DWQA_Autoclosure();
			$this->captcha     = new DWQA_Captcha();
			$this->anonymous   = new DWQA_Anonymous();

			$this->filter  = new DWQA_Filter();
			$this->session = new DWQA_Session();

			$this->metaboxes = new DWQA_Metaboxes();

			//integrate Ultimate Member
			$this->DWQA_Ultimate_Member = new DWQA_Ultimate_Member();

			$this->helptab        = new DWQA_Helptab();
			$this->pointer_helper = new DWQA_PointerHelper();
			$this->admin_notices  = new DWQA_Admin_Notice();
			$this->logs           = new DWQA_Log();

			new DWQA_Admin_Upgrade();

			if ( defined( 'DWQA_TEST_MODE' ) && DWQA_TEST_MODE ) {

			}

			// All init action of plugin will be included in
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'widgets_init', array( $this, 'widgets_init' ) );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			register_activation_hook( __FILE__, array( $this, 'activate_hook' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate_hook' ) );

			add_filter( 'http_request_args', array( $this, 'prevent_update' ), 10, 2 );

			add_action( 'bp_include', array( $this, 'dwqa_setup_buddypress' ), 10 );
		}

		public function dwqa_setup_buddypress() {
			// Include the BuddyPress Component
			require( DWQA_DIR . 'inc/extend/buddypress/loader.php' );

			// Instantiate BuddyPress for bbPress
			$this->DWQA_Buddypress = new DWQA_QA_Component();
		}

		public static function instance() {
			static $_instance = null;

			if ( is_null( $_instance ) ) {
				$_instance = new self();
			}

			return $_instance;
		}

		public function define_constants() {
			$defines = array(
				'DWQA_DIR'            => plugin_dir_path( __FILE__ ),
				'DWQA_URI'            => plugin_dir_url( __FILE__ ),
				'DWQA_TEMP_DIR'       => trailingslashit( get_template_directory() ),
				'DWQA_TEMP_URL'       => trailingslashit( get_template_directory_uri() ),
				'DWQA_STYLESHEET_DIR' => trailingslashit( get_stylesheet_directory() ),
				'DWQA_STYLESHEET_URL' => trailingslashit( get_stylesheet_directory_uri() )
			);

			foreach ( $defines as $k => $v ) {
				if ( ! defined( $k ) ) {
					define( $k, $v );
				}
			}
		}

		public function includes() {
			// Add autoload class
			require_once DWQA_DIR . 'inc/autoload.php';
			require_once DWQA_DIR . 'inc/helper/functions.php';
			require_once DWQA_DIR . 'inc/helper/theme-compatibility.php';
			require_once DWQA_DIR . 'inc/helper/plugin-compatibility.php';
			// require_once DWQA_DIR . 'inc/helper/akismet.php';
			require_once DWQA_DIR . 'inc/deprecated.php';

			require_once DWQA_DIR . 'inc/widgets/Closed_Question.php';
			require_once DWQA_DIR . 'inc/widgets/Latest_Question.php';
			require_once DWQA_DIR . 'inc/widgets/Popular_Question.php';
			require_once DWQA_DIR . 'inc/widgets/Related_Question.php';
			require_once DWQA_DIR . 'inc/widgets/Category_Question.php';
			require_once DWQA_DIR . 'inc/widgets/unanswered_Question.php';

			require_once DWQA_DIR . 'inc/widgets/Ask_Form.php';
			require_once DWQA_DIR . 'inc/widgets/Leaderboard.php';

			require_once DWQA_DIR . 'inc/extend/ultimate-member/loader.php';
		}

		public function widgets_init() {
			$widgets = array(
				'DWQA_Widgets_Closed_Question',
				'DWQA_Widgets_unanswered_Question',
				'DWQA_Widgets_Latest_Question',
				'DWQA_Widgets_Popular_Question',
				'DWQA_Widgets_Related_Question',
				'DWQA_Widget_Ask_Form',
				'DWQA_Widget_Categories_List',
				'DWQA_Leaderboard_Widget'
			);

			foreach ( $widgets as $widget ) {
				register_widget( $widget );
			}
		}

		public function init() {
			global $dwqa_sript_vars, $dwqa_template, $dwqa_general_settings;

			$active_template = $this->template->get_template();
			//Scripts var

			$question_category_rewrite = $dwqa_general_settings['question-category-rewrite'];
			$question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';
			$question_tag_rewrite      = $dwqa_general_settings['question-tag-rewrite'];
			$question_tag_rewrite      = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';
			$dwqa_sript_vars           = apply_filters( 'dwqa_sript_vars', array(
				'is_logged_in'                   => is_user_logged_in(),
				'plugin_dir_url'                 => DWQA_URI,
				'code_icon'                      => DWQA_URI . 'inc/templates/' . $active_template . '/assets/img/icon-code.png',
				'ajax_url'                       => admin_url( 'admin-ajax.php' ),
				'text_next'                      => __( 'Next', 'dwqa' ),
				'text_prev'                      => __( 'Prev', 'dwqa' ),
				'questions_archive_link'         => get_post_type_archive_link( 'dwqa-question' ),
				'error_missing_question_content' => __( 'Please enter your question', 'dwqa' ),
				'error_question_length'          => __( 'Your question must be at least 2 characters in length', 'dwqa' ),
				'error_valid_email'              => __( 'Enter a valid email address', 'dwqa' ),
				'error_valid_user'               => __( 'Enter a question title', 'dwqa' ),
				'error_valid_name'               => __( 'Please add your name', 'dwqa' ),
				'error_missing_answer_content'   => __( 'Please enter your answer', 'dwqa' ),
				'error_missing_comment_content'  => __( 'Please enter your comment content', 'dwqa' ),
				'error_not_enought_length'       => __( 'Comment must have more than 2 characters', 'dwqa' ),
				'search_not_found_message'       => __( 'Not found! Try another keyword.', 'dwqa' ),
				'search_enter_get_more'          => __( 'Or press <strong>ENTER</strong> to get more questions', 'dwqa' ),
				'comment_edit_submit_button'     => __( 'Update', 'dwqa' ),
				'comment_edit_link'              => __( 'Edit', 'dwqa' ),
				'comment_edit_cancel_link'       => __( 'Cancel', 'dwqa' ),
				'comment_delete_confirm'         => __( 'Do you want to delete this comment?', 'dwqa' ),
				'answer_delete_confirm'          => __( 'Do you want to delete this answer?', 'dwqa' ),
				'answer_update_privacy_confirm'  => __( 'Do you want to update this answer?', 'dwqa' ),
				'report_answer_confirm'          => __( 'Do you want to report this answer?', 'dwqa' ),
				'flag'                           => array(
					'label'        => __( 'Report', 'dwqa' ),
					'label_revert' => __( 'Undo', 'dwqa' ),
					'text'         => __( 'This answer will be marked as spam and hidden. Do you want to flag it?', 'dwqa' ),
					'revert'       => __( 'This answer was flagged as spam. Do you want to show it', 'dwqa' ),
					'flag_alert'   => __( 'This answer was flagged as spam', 'dwqa' ),
					'flagged_hide' => __( 'hide', 'dwqa' ),
					'flagged_show' => __( 'show', 'dwqa' ),
				),
				'follow_tooltip'                 => __( 'Follow This Question', 'dwqa' ),
				'unfollow_tooltip'               => __( 'Unfollow This Question', 'dwqa' ),
				'stick_tooltip'                  => __( 'Pin this question to top', 'dwqa' ),
				'unstick_tooltip'                => __( 'Unpin this question from top', 'dwqa' ),
				'question_category_rewrite'      => $question_category_rewrite,//$question_category_rewrite,
				'question_tag_rewrite'           => $question_tag_rewrite, //$question_tag_rewrite,
				'delete_question_confirm'        => __( 'Do you want to delete this question?', 'dwqa' )
			) );

			$this->flush_rules();
		}

		// Update rewrite url when active plugin
		public function activate_hook() {
			$this->permission->prepare_permission_caps();

			flush_rewrite_rules();
			//Auto create question page
			$options = get_option( 'dwqa_options' );

			$options['enable-review-question']                   = 1;
			$options['enable-private-question']                  = 1;
			$options['show-status-icon']                         = 1;
			$options['show-all-answers-on-single-question-page'] = 1;
			$options['posts-per-page']                           = 15;
			$options['max-size-upload']                          = 512;
			$options['max-files-upload']                         = 2;
			$options['accept-upload-extension']                  = 'txt|jpg|pdf';

			if ( ! isset( $options['pages']['archive-question'] ) || ( isset( $options['pages']['archive-question'] ) && ! get_page( $options['pages']['archive-question'] ) ) ) {
				$args          = array(
					'post_title'   => __( 'DWQA Questions', 'dwqa' ),
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_content' => '[dwqa-list-questions]',
				);
				$question_page = get_page_by_path( sanitize_title( $args['post_title'] ) );
				if ( ! $question_page ) {
					$options['pages']['archive-question'] = wp_insert_post( $args );
				} else {
					// Page exists
					$options['pages']['archive-question'] = $question_page->ID;
				}
			}

			if ( ! isset( $options['pages']['submit-question'] ) || ( isset( $options['pages']['submit-question'] ) && ! get_page( $options['pages']['submit-question'] ) ) ) {

				$args     = array(
					'post_title'   => __( 'DWQA Ask Question', 'dwqa' ),
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_content' => '[dwqa-submit-question-form]',
				);
				$ask_page = get_page_by_path( sanitize_title( $args['post_title'] ) );

				if ( ! $ask_page ) {
					$options['pages']['submit-question'] = wp_insert_post( $args );
				} else {
					// Page exists
					$options['pages']['submit-question'] = $ask_page->ID;
				}
			}

			// Valid page content to ensure shortcode was inserted
			$questions_page_content = get_post_field( 'post_content', $options['pages']['archive-question'] );
			if ( strpos( $questions_page_content, '[dwqa-list-questions]' ) === false ) {
				$questions_page_content = str_replace( '[dwqa-submit-question-form]', '', $questions_page_content );
				wp_update_post( array(
					'ID'           => $options['pages']['archive-question'],
					'post_content' => $questions_page_content . '[dwqa-list-questions]',
				) );
			}

			$submit_question_content = get_post_field( 'post_content', $options['pages']['submit-question'] );
			if ( strpos( $submit_question_content, '[dwqa-submit-question-form]' ) === false ) {
				$submit_question_content = str_replace( '[dwqa-list-questions]', '', $submit_question_content );
				wp_update_post( array(
					'ID'           => $options['pages']['submit-question'],
					'post_content' => $submit_question_content . '[dwqa-submit-question-form]',
				) );
			}

			$first_time_install = get_option( 'dwqa-db-version', false );

			if ( ! $first_time_install ) {
				update_option( 'dwqa-db-version', $this->db_version );
			}

			update_option( 'dwqa_options', $options );
			update_option( 'dwqa_plugin_activated', true );
			// dwqa_posttype_init();
		}

		public function deactivate_hook() {
			$this->permission->remove_permision_caps();

			wp_clear_scheduled_hook( 'dwqa_hourly_event' );

			flush_rewrite_rules();
		}

		public function flush_rules() {
			if ( get_option( 'dwqa_plugin_activated', false ) ) {
				delete_option( 'dwqa_plugin_activated' );
				flush_rewrite_rules();
			}
		}

		public function get_last_update() {
			return $this->last_update;
		}

		public function prevent_update( $r, $url ) {
			if ( false !== strpos( $url, 'api.wordpress.org/plugins/update-check/' ) || false !== strpos( $url, 'api.wordpress.org/core/version-check/1.7' ) ) {
				if ( isset( $r['body']['plugins'] ) ) {
					$plugins = json_decode( $r['body']['plugins'] );

					if ( ! empty( $plugins ) ) {
						$my_plugin = plugin_basename( __FILE__ );
						unset( $plugins->plugins->{$my_plugin} );
						foreach ( $plugins->active as $k => $v ) {
							if ( $v == $my_plugin ) {
								unset( $plugins->active->{$k} );
							}
						}
						$r['body']['plugins'] = json_encode( $plugins );
					}
				}
		}

			return $r;
		}

		public function load_textdomain() {
			$locale = get_locale();
			$mo     = 'dwqa-' . $locale . '.mo';

			load_textdomain( 'dwqa', WP_LANG_DIR . '/dwqa/' . $mo );
			load_textdomain( 'dwqa', plugin_dir_path( __FILE__ ) . 'languages/' . $mo );
			load_plugin_textdomain( 'dwqa' );
		}
	}

	function dwqa() {
		return DW_Question_Answer::instance();
	}

	$GLOBALS['dwqa'] = dwqa();

endif;
