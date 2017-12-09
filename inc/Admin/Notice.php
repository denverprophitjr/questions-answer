<?php

class DWQA_Admin_Notice {
	private $notices;
	private $hidden = array( 'upgrade_to_pro' );

	public function __construct() {
		$this->init();

		add_action( 'admin_print_styles', array( $this, 'print_notice' ) );
		add_action( 'wp_loaded', array( $this, 'hide_notice' ) );
	}

	public function init() {
		$this->notices = get_option( 'dwqa_admin_notices', array() );
		$is_hidden     = get_option( 'dwqa_admin_hidden_notices', array() );
		$this->hidden  = array_merge( $is_hidden, $this->hidden );

		foreach ( $this->notices as $notice => $cb ) {
			if ( in_array( $notice, $this->hidden ) ) {
				unset( $this->notices[ $notice ] );
			}
		}
	}

	public function add_notice( $key, $callback ) {
		$notices         = get_option( 'dwqa_admin_notices', array() );
		$notices[ $key ] = $callback;
		$this->notices   = $notices;
		update_option( 'dwqa_admin_notices', $notices );
	}

	public function remove_notice( $key ) {
		$notices = get_option( 'dwqa_admin_notices', array() );

		if ( isset( $notices[ $key ] ) ) {
			unset( $notices[ $key ] );
		}
		$this->notices  = $notices;
		$this->hidden[] = $key;

		update_option( 'dwqa_admin_hidden_notices', $this->hidden );
		update_option( 'dwqa_admin_notices', $notices );
	}

	public function has_notice( $key ) {
		return isset( $this->notices[ $key ] ) ? true : false;
	}

	public function hide_notice() {
		if ( isset( $_GET['dwqa-hide-notice'] ) && isset( $_GET['dwqa-notice-nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['dwqa-notice-nonce'], 'dwqa_hide_notice_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'dwqa' ) );
			}

			$notice = sanitize_text_field( $_GET['dwqa-hide-notice'] );
			$this->remove_notice( $notice );
			do_action( 'dwqa_hide_' . $notice . '_notice' );
		}
	}

	public function print_notice() {
		if ( $this->notices ) {
			foreach ( $this->notices as $notice => $callback ) {
				if ( apply_filters( 'dwqa_show_admin_notices', true, $this->notices ) ) {
					add_action( 'admin_notices', $callback );
				}
			}
		}
	}
}
