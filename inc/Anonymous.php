<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWQA_Anonymous {

	private $dwqa_permission;

	public function __construct() {
		$dwqa_permission = get_option( 'dwqa_permission' );
		if ( ! $dwqa_permission ) {
			return;
		}

		if ( isset( $dwqa_permission['anonymous']['question']['post'] ) && $dwqa_permission['anonymous']['question']['post'] ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
	}

	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=dwqa-question', __( 'Anonymous Listing', 'dwqa' ), __( 'Anonymous Listing', 'dwqa' ), 'manage_options', 'dwqa-anonymous', array(
			$this,
			'anonymous_display'
		) );
	}

	public function anonymous_display() {
		require_once DWQA_DIR . 'inc/class/class-display-anonymous-list-table.php';
		$anonymousTable = new Anonymous_List_Table();
		// $anonymousTable->process_bulk_action();

		echo '<div class="wrap"><h1>Anonymous List</h1>';
		$columns = array(
			'id'       => 'id',
			'question' => __( 'Question', 'dwqa' ),
			'email'    => __( 'Email', 'dwqa' ),
			'name'     => __( 'Name', 'dwqa' )

		);

		$hiddens  = array(
			'id'
		);
		$sortable = array();
		$anonymousTable->edit_columns( $columns );
		$anonymousTable->edit_hiddens( $hiddens );
		$anonymousTable->edit_sortable( $sortable );
		$anonymousTable->edit_perpage( 11 );

		$args = array(
			'post_type'      => 'dwqa-question',
			'post_status'    => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit' ),
			'meta_query'     => array(
				array(
					'key'     => '_dwqa_is_anonymous',
					'value'   => '1',
					'compare' => '='
				),
			),
			'posts_per_page' => 20
		);

		if ( isset( $_POST['s'] ) && $_POST['s'] != '' ) {
			$args['search_question_title'] = $_POST['s'];

			add_filter( 'posts_where', array( $this, 'questions_where' ), 10, 2 );
			$results = new WP_Query( $args );
			$results = $results->posts;
			remove_filter( 'posts_where', array( $this, 'questions_where' ), 10, 2 );
		} else {
			$results = get_posts( $args );
		}


		$_data = $this->setup_data_list( $results );
		$anonymousTable->set_items( $_data );
		$anonymousTable->prepare_items();

		echo '<form method="POST">';
		$anonymousTable->search_box( 'Search', 's' );
		$anonymousTable->display();
		echo '</form>';
		echo '</div>';
	}

	public function questions_where( $where, &$wp_query ) {
		global $wpdb;
		if ( $search_term = $wp_query->get( 'search_question_title' ) ) {
			/*using the esc_like() in here instead of other esc_sql()*/
			$search_term = $wpdb->esc_like( $search_term );
			$search_term = ' \'%' . $search_term . '%\'';
			$where       .= ' AND ' . $wpdb->posts . '.post_title LIKE ' . $search_term;
		}

		return $where;
	}

	private function setup_data_list( $list ) {

		$data_list = array();
		foreach ( $list as $item ) {
			$question_id     = $item->ID;
			$question        = '<a href="' . get_permalink( $question_id ) . '">' . $item->post_title . '</a>';
			$anonymous_email = get_post_meta( $question_id, '_dwqa_anonymous_email', true );
			$anonymous_name  = get_post_meta( $question_id, '_dwqa_anonymous_name', true );

			$temp = array(
				'id'       => $question_id,
				'email'    => $anonymous_email,
				'name'     => $anonymous_name,
				'question' => $question
			);

			array_push( $data_list, $temp );
		}

		return $data_list;
	}

}
