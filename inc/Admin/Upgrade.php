<?php

class DWQA_Admin_Upgrade {
	private $start_version = '1.0.6';

	public function __construct() {
		add_action( 'init', array( $this, 'add_notice' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'wp_ajax_dwqa_upgrades', array( $this, 'update' ) );
	}

	public function add_admin_page() {
		add_submenu_page( null, __( 'DWQA Upgrades', 'dwqa' ), __( 'DWQA Upgrades', 'dwqa' ), 'manage_options', 'dwqa-upgrades', array(
			$this,
			'screen'
		) );
	}

	public function screen() {
		wp_enqueue_script( 'jquery' );
		?>
        <div class="wrap">
            <h2><?php echo get_admin_page_title() ?></h2>
            <p><?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished...', 'dwqa' ) ?></p>
            <span class="spinner" style="visibility: visible; float: none;"></span>
        </div>
        <script type="text/javascript">
            function do_upgrade() {
                jQuery.post(ajaxurl, {action: 'dwqa_upgrades'}, function (res) {
                    if (res === 'complete') {
                        window.location.href = 'edit.php?post_type=dwqa-question';
                    }

                    if (res === 'continue') {
                        do_upgrade();
                    }
                })
            }

            do_upgrade();
        </script>
		<?php
	}

	public function add_notice() {
		//only admin
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$current_db_version = get_option( 'dwqa-db-version', $this->start_version );
		$db_version         = dwqa()->db_version;
		if ( version_compare( $current_db_version, $db_version, '<' ) ) {
			dwqa()->admin_notices->add_notice( 'update', __CLASS__ . '::print_notice' );
		}
	}

	public static function print_notice() {
		?>
        <div id="message" class="updated dwqa-message dwqa-connect">
            <p><?php _e( '<strong>DW Question & Answer Data Update Required</strong> &#8211; We just need to update your install to the latest version', 'dwqa' ); ?></p>
            <p class="submit"><a
                        href="<?php echo esc_url( add_query_arg( 'dwqa-upgrade', 'true', admin_url( 'admin.php?page=dwqa-upgrades' ) ) ); ?>"
                        class="dwqa-update-now button-primary"><?php _e( 'Run the updater', 'dwqa' ); ?></a></p>
        </div>
        <script type="text/javascript">
            jQuery('.dwqa-update-now').click('click', function () {
                return window.confirm('<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'dwqa' ) ); ?>');
            });
        </script>
		<?php
	}

	public function update() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to do upgrade.', 'dwqa' ) );
		}

		$current_db_version = get_option( 'dwqa-db-version', $this->start_version );
		update_option( 'dwqa_doing_upgrade', true );

		// run upgrade version 1.0.7
		if ( version_compare( $current_db_version, '1.0.7', '<' ) ) {
			$this->upgrade_v107();
		}

		delete_option( 'dwqa_doing_upgrade' );
		dwqa()->admin_notices->remove_notice( 'update' );

		die( 'complete' );
	}

	/**
	 * This update will update your answer parent for fast query
	 */
	public function upgrade_v107() {
		global $wpdb;
		$step   = get_option( 'dwqa_upgrades_step', 0 );
		$next   = intval( $step ) + 1;
		$limit  = 20;
		$offset = (int) $step * (int) $limit;

		$sql = "SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = 'dwqa-answer' LIMIT {$offset}, {$limit}";

		$answer_ids = $wpdb->get_col( $sql );
		if ( $answer_ids && ! is_wp_error( $answer_ids ) ) {
			foreach ( $answer_ids as $answer_id ) {
				$question_id = get_post_meta( $answer_id, '_question', true );
				if ( $question_id ) {
					$sql    = "UPDATE {$wpdb->posts} SET post_parent = {$question_id} WHERE ID = {$answer_id}";
					$status = $wpdb->query( $sql );
				}
			}
			update_option( 'dwqa_upgrades_step', $next );
			die( 'continue' );
		}

		delete_option( 'dwqa_upgrades_step' );
		update_option( 'dwqa-db-version', '1.0.7' );
	}
}
