<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Reported_List_Table extends WP_List_Table {
	private $_table = '';
	private $_columns = array();
	private $_hiddens = array();
	private $_sortable = array();
	private $_perpage = 5;
	private $_filterDate = array();


	function no_items() {
		_e( 'No data found!' );
	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="reported[]" value="%s" />', $item['id']
		);
	}

	function get_columns() {
		$this->_columns = array( 'cb' => '<input type="checkbox" />' ) + $this->_columns;

		return $this->_columns;
	}

	function get_hiddens() {
		return $this->_hiddens;
	}

	function get_sortable() {
		return $this->_sortable;
	}

	function get_perpage() {
		return $this->_perpage;
	}

	function get_filterDate() {
		return $this->_filterDate;
	}

	function edit_table( $table ) {
		require_once RB_CLASS . 'class-sql.php';
		$this->_table = new rb_sql( $table );
	}

	function edit_columns( $columns ) {
		$this->_columns = $columns;
	}

	function edit_hiddens( $hiddens ) {
		$this->_hiddens = $hiddens;
	}

	function edit_sortable( $sortable ) {
		$this->_sortable = $sortable;
	}

	function edit_filterDate( $filterDate ) {
		$this->_filterDate = $filterDate;
	}

	function edit_perpage( $perpage ) {
		$this->_perpage = $perpage;
	}

	function usort_reorder( $a, $b ) {
		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'order';

		// Determine sort order
		switch ( $orderby ) {
			case 'date':
				$result = $this->lv_sort_date( $a[ $orderby ], $b[ $orderby ] );
				break;
			case 'order':
				$result = $this->lv_sort_id( $a['id'], $b['id'] );
				break;
			default:
				$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		}
		// If no order, default to desc
		$order = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'desc';

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : - $result;
	}

	function lv_sort_date( $a, $b ) {
		$array_a  = explode( "/", $a );
		$array_b  = explode( "/", $b );
		$string_a = $array_a[2] . '-' . $array_a[1] . '-' . $array_a[0] . ' 00:00:00';
		$string_b = $array_b[2] . '-' . $array_b[1] . '-' . $array_b[0] . ' 00:00:00';
		if ( strtotime( $string_a ) > strtotime( $string_b ) ) {
			return 1;
		}
		if ( strtotime( $string_a ) < strtotime( $string_b ) ) {
			return - 1;
		}

		return 0;
	}

	function lv_sort_id( $a, $b ) {
		if ( $a > $b ) {
			return 1;
		}
		if ( $a < $b ) {
			return - 1;
		}

		return 0;
	}


	function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete' )
		);

		return $actions;
	}


	function process_bulk_action() {

		// security check!
		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( 'Nope! Security check failed!' );
			}

		}

		$action = $this->current_action();

		switch ( $action ) {

			case 'delete':
				foreach ( $_POST['book'] as $id ) {
					$this->_table->delete( $id );
				}
				break;

			/* case 'save':
				wp_die( 'Save something' );
				break;
 */
			default:
				// do nothing or something else
				return;
				break;
		}

		return;
	}

	function prepare_items( $data ) {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hiddens();
		$sortable = $this->get_sortable();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		usort( $data, array( &$this, 'usort_reorder' ) );

		$per_page     = $this->get_perpage();
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );
		// only ncessary because we have sample data
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,                  //WE have to calculate the total number of items
				'per_page'    => $per_page                     //WE have to determine how many items to show on a page
			)
		);
		$this->items = $data;
	}


	/* function extra_tablenav( $which ){
		if($which=="top"):
		$filterDate = $this->get_filterDate();
		
		echo '
		<div class="alignleft actions" action="" method="get">
			<label for="filter-by-date" class="screen-reader-text">Filter by date</label>
			<select name="m" id="filter-by-date">';
			if(isset($_POST['m']) ){
				
				$selected = $_POST['m'];
				echo '<option value="0">All dates</option>';
			}else{
				$selected = "";
				echo '<option selected="selected" value="0">All dates</option>';
			}
		foreach($filterDate as $key => $value){
			if($selected!="" && $selected==($value->date)){
				echo '<option selected="selected" value="'.$value->date.'">'.$value->date.'</option>';
			}else{
				echo '<option value="'.$value->date.'">'.$value->date.'</option>';
			}
		}
		echo '
			</select>
			<input type="submit" name="filter_action" id="filter-by-date-submit" class="button" value="Filter">
			
		</div>';
		endif;
	} */

}


?>
