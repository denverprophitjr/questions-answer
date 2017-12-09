<?php

class DWQA_Upload {

	public function __construct() {
		global $dwqa_general_settings;
		if ( isset( $dwqa_general_settings['show-button-upload'] ) && $dwqa_general_settings['show-button-upload'] ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'dwqa_attachments_enqueue_script' ), 10 );

			add_action( 'dwqa_prepare_add_answer', array( $this, 'dwqa_check_error_fileupload_answer' ), 10 );
			add_action( 'dwqa_prepare_update_answer', array( $this, 'dwqa_check_error_fileupload_answer' ), 10 );
			add_action( 'dwqa_before_submit_question', array( $this, 'dwqa_check_error_fileupload_answer' ), 10 );
			add_action( 'dwqa_prepare_update_question', array( $this, 'dwqa_check_error_fileupload_answer' ), 10 );

			add_action( 'dwqa_add_answer', array( $this, 'dwqa_add_answer_button_upload' ), 10, 2 );
			add_action( 'dwqa_update_answer', array( $this, 'dwqa_add_answer_button_upload' ), 10, 2 );
			add_action( 'dwqa_add_question', array(
				$this,
				'dwqa_add_answer_button_upload'
			), 10, 2 );//need care: second arg is UserID
			add_action( 'dwqa_update_question', array(
				$this,
				'dwqa_add_answer_button_upload'
			), 10, 2 );//need care: second arg is a QuestionPost

			add_action( 'dwqa_before_question_submit_button', array( $this, 'dwqa_add_fileupload_answer' ), 10 );
			add_action( 'dwqa_before_answer_submit_button', array( $this, 'dwqa_add_fileupload_answer' ), 10 );
			add_action( 'dwqa_before_edit_submit_button', array( $this, 'dwqa_add_fileupload_answer' ), 10 );

			add_action( 'dwqa_after_show_content_answer', array( $this, 'dwqa_show_fileupload_answer' ), 10, 1 );
			add_action( 'dwqa_after_show_content_question', array( $this, 'dwqa_show_fileupload_answer' ), 10, 1 );
			add_action( 'dwqa_after_show_content_edit', array( $this, 'dwqa_show_fileupload_answer' ), 10, 1 );

			add_action( 'wp_ajax_dwqa-attachments-remove-item', array( $this, 'dwqa_attachments_remove_item' ) );
			// add_action( 'wp_ajax_nopriv_dwqa-attachments-remove-item', array($this,'dwqa_attachments_remove_item' ));

		}
	}

	public function dwqa_add_answer_button_upload( $answer_id, $question_id ) {
		$uploads = array();
		// echo $answer_id;
		// echo '<pre>';
		// print_r($_FILES);
		// echo '</pre>';
		// die();
		if ( ! empty( $_FILES ) && ! empty( $_FILES['dwqa_upload'] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			global $dwqa_general_settings;
			$args_accepted    = explode( '|', $dwqa_general_settings['accept-upload-extension'] );
			$max_size         = $dwqa_general_settings['max-size-upload'] * 1024;
			$max_files        = (int) $dwqa_general_settings['max-files-upload'];
			$count_attachment = count( get_children( array(
				'post_parent' => $answer_id,
				'post_type'   => 'attachment'
			) ) );
			$count_file       = $max_files - $count_attachment;

			$overrides = array( 'test_form' => false, 'upload_error_handler' => 'dwqa_attachment_handle_upload_error' );


			foreach ( $_FILES['dwqa_upload']['error'] as $key => $error ) {
				$file_name = $_FILES['dwqa_upload']['name'][ $key ];
				$file_size = $_FILES['dwqa_upload']['size'][ $key ];
				$ext       = pathinfo( $file_name, PATHINFO_EXTENSION );
				if ( $count_file > 0 ) {
					if ( $error == UPLOAD_ERR_OK ) {
						if ( in_array( $ext, $args_accepted ) ) {
							if ( $file_size > 0 ) {
								if ( $file_size <= $max_size ) {
									$file = array(
										'name'     => $file_name,
										'type'     => $_FILES['dwqa_upload']['type'][ $key ],
										'size'     => $_FILES['dwqa_upload']['size'][ $key ],
										'tmp_name' => $_FILES['dwqa_upload']['tmp_name'][ $key ],
										'error'    => $_FILES['dwqa_upload']['error'][ $key ]
									);

									$upload = wp_handle_upload( $file, $overrides );

									if ( ! is_wp_error( $upload ) ) {
										$uploads[] = $upload;
									} else {
										dwqa_add_notice( __( 'FileUpload: ' . $upload->errors['wp_upload_error'][0], 'dwqa' ), 'error' );
									}
								} else {
									dwqa_add_notice( __( 'FileUpload: Upload file size exceeds maximum file size allowed', 'dwqa' ), 'error' );
								}
							} else {
								dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Upload file is empty.', 'dwqa' ), 'error' );
							}

						} else {
							dwqa_add_notice( __( 'FileUpload: Upload file type not allowed', 'dwqa' ), 'error' );
						}
					}
					$count_file --;
				} else {
					dwqa_add_notice( __( 'FileUpload: Upload file count exceeds maximum file count allowed', 'dwqa' ), 'error' );
				}
			}
		}

		if ( ! empty( $uploads ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			foreach ( $uploads as $upload ) {
				$wp_filetype = wp_check_filetype( basename( $upload['file'] ), null );
				$attachment  = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $upload['file'] ) ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);

				$attach_id   = wp_insert_attachment( $attachment, $upload['file'], $answer_id );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				update_post_meta( $attach_id, 'dwqa_attachment', '1' );
			}
		}
	}

	public function dwqa_check_error_fileupload_answer() {
		$uploads = array();

		if ( ! empty( $_FILES ) && ! empty( $_FILES['dwqa_upload'] ) ) {
			global $dwqa_general_settings;
			$args_accepted = explode( '|', $dwqa_general_settings['accept-upload-extension'] );
			$max_size      = $dwqa_general_settings['max-size-upload'] * 1024;

			foreach ( $_FILES['dwqa_upload']['error'] as $key => $error ) {
				$file_name = $_FILES['dwqa_upload']['name'][ $key ];
				$file_size = $_FILES['dwqa_upload']['size'][ $key ];
				$ext       = pathinfo( $file_name, PATHINFO_EXTENSION );

				if ( $error == UPLOAD_ERR_OK ) {
					if ( in_array( $ext, $args_accepted ) ) {
						if ( $file_size > 0 ) {
							if ( $file_size <= $max_size ) {

							} else {
								dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Upload file size exceeds maximum file size allowed', 'dwqa' ), 'error' );
							}
						} else {
							dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Upload file is empty.', 'dwqa' ), 'error' );
						}
					} else {
						dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Upload file type not allowed', 'dwqa' ), 'error' );
					}
				} else {
					switch ( $error ) {

						/* case 'UPLOAD_ERR_NO_FILE':
							dwqa_add_notice( __( 'FileUpload('.$file_name.'): File not uploaded', 'dwqa' ), 'error' );
							break; */
						case 'UPLOAD_ERR_INI_SIZE':
							dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Upload file size exceeds PHP maximum file size allowed', 'dwqa' ), 'error' );
							break;
						case 'UPLOAD_ERR_FORM_SIZE':
							dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Upload file size exceeds FORM specified file size', 'dwqa' ), 'error' );
							break;
						case 'UPLOAD_ERR_PARTIAL':
							dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Upload file only partially uploaded', 'dwqa' ), 'error' );
							break;
						case 'UPLOAD_ERR_CANT_WRITE':
							dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Can\'t write file to the disk', 'dwqa' ), 'error' );
							break;
						case 'UPLOAD_ERR_NO_TMP_DIR':
							dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Temporary folder for upload is missing', 'dwqa' ), 'error' );
							break;
						case 'UPLOAD_ERR_EXTENSION':
							dwqa_add_notice( __( 'FileUpload(' . $file_name . '): Server extension restriction stopped upload', 'dwqa' ), 'error' );
							break;
						default:
							break;
					}
				}
			}
		}
	}

	public function dwqa_add_fileupload_answer() {
		global $dwqa_general_settings;

		echo '<div id="dwqa-attachments-wrap-button-upload">
			<input type="file" name="dwqa_upload[]" class="dwqa-attachments-button-upload"/>
		</div>';
		echo '<div class="dwqa-attachments-description">
			<p>Accept types: ' . str_replace( '|', ', ', $dwqa_general_settings['accept-upload-extension'] ) . '</p>
			<span id="dwqa-attachments-add-button-upload">Add another file</span>
		</div>';
		echo '<input type="hidden" id="dwqa-attachments-max-files-upload" value="' . $dwqa_general_settings['max-files-upload'] . '"/>';
	}

	public function dwqa_show_fileupload_answer( $post_id ) {
		global $dwqa_general_settings;

		$args        = array(
			'post_type'   => 'attachment',
			'numberposts' => - 1,
			'post_status' => null,
			'post_parent' => $post_id,
			'orderby'     => 'ID',
			'order'       => 'ASC'
		);
		$attachments = get_posts( $args );

		$remove_attachment = '';

		//remove if have permission
		$edit_id = isset( $_GET['edit'] ) && is_numeric( $_GET['edit'] ) ? $_GET['edit'] : false;
		if ( $edit_id ) {
			if ( dwqa_current_user_can( 'edit_answer', $post_id ) || dwqa_current_user_can( 'manage_answer' ) ) {
				$remove_attachment = '<span class="dwqa-attachments-remove-item" data-id="{attachmentid}" data-post="{postid}">Delete</span>';
			}
		}


		if ( ! empty( $attachments ) ) {
			echo '<h6>Attachments</h6>';
			foreach ( $attachments as $attachment ) {

				$file        = get_attached_file( $attachment->ID );
				$ext         = pathinfo( $file, PATHINFO_EXTENSION );
				$filename    = pathinfo( $file, PATHINFO_BASENAME );
				$file_url    = wp_get_attachment_url( $attachment->ID );
				$file_action = str_replace( '{attachmentid}', $attachment->ID, $remove_attachment );
				$file_action = str_replace( '{postid}', $post_id, $file_action );

				echo '<div class="dwqa-attachments-item-wrap"><a href="' . $file_url . '" target="_blank">' . $filename . '</a>' . $file_action . '</div>';
			}
			echo '<input type="hidden" id="dwqa_attachments_remove_items_nonce" value="' . wp_create_nonce( '_dwqa_attachments_remove_item_nonce' ) . '"/>';
		}

	}

	public function dwqa_attachments_enqueue_script() {
		wp_enqueue_script( 'dwqa-attachments-button-upload-script', DWQA_URI . 'assets/js/dwqa-attachments-button-upload.js', false );
		wp_enqueue_style( 'dwqa-attachments-style', DWQA_URI . 'assets/css/dwqa-attachments-style.css', false );
	}

	public function dwqa_attachments_remove_item() {
		$attachment_id = isset( $_POST['attachment_id'] ) ? $_POST['attachment_id'] : false;
		$post_id       = isset( $_POST['post_id'] ) ? $_POST['post_id'] : false;
		$result        = array();
		if ( ! $post_id ) {
			$result = array(
				'error_code'    => 'attachment_remove',
				'error_message' => __( 'Question or answer is missing.', 'dwqa' ),
			);
			wp_send_json_error( $result );
			die();
		}
		if ( ! $attachment_id ) {
			$result = array(
				'error_code'    => 'attachment_remove',
				'error_message' => __( 'Attachment is missing.', 'dwqa' ),
			);
			wp_send_json_error( $result );
			die();
		}
		if ( ! dwqa_current_user_can( 'edit_answer', $post_id ) && ! dwqa_current_user_can( 'manage_answer' ) ) {
			$result = array(
				'error_code'    => "attachment_remove",
				'error_message' => __( "You do not have permission to edit answer.", 'dwqa' ),
			);
			wp_send_json_error( $result );
			die();
		}

		if ( ! isset( $_POST['nonce'] ) && ! wp_verify_nonce( esc_html( $_POST['nonce'] ), '_dwqa_attachments_remove_item_nonce' ) ) {
			$result = array(
				'error_code'    => "attachment_remove",
				'error_message' => __( "Hello, Are you cheating huh?", 'dwqa' ),
			);
			wp_send_json_error( $result );
			die();
		}
		wp_delete_attachment( $attachment_id );
		wp_send_json_success();
	}
}
