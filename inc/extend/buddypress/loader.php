<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DWQA_QA_Component' ) ) :

	class DWQA_QA_Component extends BP_Component {

		public function __construct() {
			parent::start(
				'dwqa',
				__( 'DWQA', 'dwqa' ),
				DWQA_DIR . 'inc/extend/buddypress/'
			);

			add_action( 'dwqa_register_middle_setting_field', array( $this, 'bp_dwqa_setting_name' ) );

			$this->includes();
			$this->setup_globals();
			$this->fully_loaded();

		}

		public function dwqa_bp_name() {
			global $dwqa_general_settings;
			$bp_name = isset( $dwqa_general_settings['dwqa-bp-name'] ) ? $dwqa_general_settings['dwqa-bp-name'] : '';
			echo '<p><input id="dwqa_bp_name" type="text" name="dwqa_options[dwqa-bp-name]" class="medium-text" value="' . $bp_name . '" ></p>';
		}

		public function bp_dwqa_setting_name() {
			add_settings_section(
				'dwqa-bp-settings',
				__( 'BuddyPress Settings', 'dwqa' ),
				false,
				'dwqa-settings'
			);
			add_settings_field(
				'dwqa_options[dwqa-bp-name]',
				__( 'Tab name', 'dwqa' ),
				array( $this, 'dwqa_bp_name' ),
				'dwqa-settings',
				'dwqa-bp-settings'
			);
		}


		public function includes( $includes = array() ) {

			$includes[] = 'functions.php';

			if ( bp_is_active( 'notifications' ) ) {
				$includes[] = 'notifications.php';
			}

			parent::includes( $includes );
		}

		public function setup_globals( $args = array() ) {
			$bp = buddypress();


			// define name
			if ( ! defined( 'BP_DWQA_NAME' ) ) {
				global $dwqa_general_settings;

				$bp_name = isset( $dwqa_general_settings['dwqa-bp-name'] ) ? $dwqa_general_settings['dwqa-bp-name'] : 'DWQA';
				define( 'BP_DWQA_NAME', $bp_name );
				define( 'BP_DWQA_SLUG', sanitize_title( $bp_name, 'dwqa' ) );//generate slug by name
			}

			// Define a slug, if necessary
			if ( ! defined( 'BP_DWQA_SLUG' ) ) {
				define( 'BP_DWQA_SLUG', 'dwqa' );
			}

			// define question, answer slug
			if ( ! defined( 'BP_DWQA_SLUG_QUESTION' ) ) {
				define( 'BP_DWQA_SLUG_QUESTION', BP_DWQA_SLUG . '-' . sanitize_title( __( 'question', 'dwqa' ) ), 'question' );
			}
			if ( ! defined( 'BP_DWQA_SLUG_ANSWER' ) ) {
				define( 'BP_DWQA_SLUG_ANSWER', BP_DWQA_SLUG . '-' . sanitize_title( __( 'answer', 'dwqa' ) ), 'answer' );
			}


			$args = array(
				'path'          => BP_PLUGIN_DIR,
				'slug'          => BP_DWQA_SLUG,
				'root_slug'     => BP_DWQA_SLUG,
				'has_directory' => false,
				'search_string' => __( 'Search ' . BP_DWQA_NAME . '...', 'dwqa' ),
			);

			parent::setup_globals( $args );
		}

		public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

			// Stop if there is no user displayed or logged in
			if ( ! is_user_logged_in() && ! bp_displayed_user_id() ) {
				return;
			}

			// Define local variable(s)
			$user_domain = '';


			// Add 'DWQA' to the main navigation
			$main_nav = array(
				'name'                => BP_DWQA_NAME,
				'slug'                => $this->slug,
				'position'            => 80,
				'screen_function'     => 'dp_dwqa_screen_questions',
				'default_subnav_slug' => BP_DWQA_SLUG_QUESTION, //dwqa-question
				'item_css_id'         => $this->id
			);

			// Determine user to use
			if ( bp_displayed_user_id() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return;
			}

			// User link
			$dwqa_link = trailingslashit( $user_domain . $this->slug );

			$sub_nav[] = array(
				'name'            => __( 'Questions', 'dwqa' ),
				'slug'            => BP_DWQA_SLUG_QUESTION, //dwqa-question
				'parent_url'      => $dwqa_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'dp_dwqa_screen_questions',
				'position'        => 20,
				'item_css_id'     => 'topics'
			);

			$sub_nav[] = array(
				'name'            => __( 'Answers', 'dwqa' ),
				'slug'            => BP_DWQA_SLUG_ANSWER, //dwqa-answer
				'parent_url'      => $dwqa_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'dp_dwqa_screen_answers',
				'position'        => 20,
				'item_css_id'     => 'topics'
			);

			parent::setup_nav( $main_nav, $sub_nav );
		}

		/**
		 * Set up the admin bar
		 *
		 * @since bbPress (r3552)
		 */
		public function setup_admin_bar( $wp_admin_nav = array() ) {
			if ( ! bp_use_wp_admin_bar() || defined( 'DOING_AJAX' ) ) {
				return;
			}
			// Menus for logged in user
			if ( is_user_logged_in() ) {

				// Setup the logged in user variables
				$user_domain = bp_loggedin_user_domain();
				$dwqa_link   = trailingslashit( $user_domain . $this->slug );

				// Add the "My Account" sub menus
				$wp_admin_nav[] = array(
					'parent' => buddypress()->my_account_menu_id,
					'id'     => 'my-account-' . $this->id,
					'title'  => BP_DWQA_NAME,
					'href'   => trailingslashit( $dwqa_link )
				);
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-question',
					'title'  => __( 'Questions', 'dwqa' ),
					'href'   => trailingslashit( $dwqa_link )
				);
				$wp_admin_nav[] = array(
					'parent' => 'my-account-' . $this->id,
					'id'     => 'my-account-' . $this->id . '-answer',
					'title'  => __( 'Answers', 'dwqa' ),
					'href'   => trailingslashit( $dwqa_link ) . 'dwqa-answer'
				);

			}

			parent::setup_admin_bar( $wp_admin_nav );
		}

		private function fully_loaded() {
			do_action_ref_array( 'bp_dwqa_buddypress_loaded', array( $this ) );
		}
	}
endif;
