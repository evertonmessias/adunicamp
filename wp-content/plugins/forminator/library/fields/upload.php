<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Upload
 *
 * @since 1.0
 */
class Forminator_Upload extends Forminator_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'upload';

	/**
	 * @var string
	 */
	public $type = 'upload';

	/**
	 * @var int
	 */
	public $position = 14;

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @var string
	 */
	public $category = 'standard';

	/**
	 * @var string
	 */
	public $icon = 'sui-icon-download';

	/**
	 * Forminator_Upload constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = __( 'File Upload', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		$default_all = array(
			'all-image',
			'all-video',
			'all-document',
			'all-audio',
			'all-archive',
			'all-text',
			'all-spreadsheet',
			'all-interactive',
		);

		$mimes = get_allowed_mime_types();

		$file_type = array_merge( $default_all, array_keys( $mimes ) );

		return array(
			'field_label'  => __( 'Upload file', 'forminator' ),
			'filetypes'    => $file_type,
			'file-type'    => 'single',
			'file-limit'   => 'unlimited',
			'upload-limit' => 8,
			'filesize'     => 'MB',
		);
	}

	/**
	 * Autofill Setting
	 *
	 * @since 1.0.5
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function autofill_settings( $settings = array() ) {
		// Unsupported Autofill.
		$autofill_settings = array();

		return $autofill_settings;
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0
	 *
	 * @param $field
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function markup( $field, $settings = array() ) {

		$this->field = $field;

		$html        = '';
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$required    = self::get_property( 'required', $field, false );
		$design      = $this->get_form_style( $settings );
		$label       = esc_html( self::get_property( 'field_label', $field, '' ) );
		$description = self::get_property( 'description', $field, '' );
		$file_type   = self::get_property( 'file-type', $field, 'single' );
		$form_id     = isset( $settings['form_id'] ) ? $settings['form_id'] : 0;

		if ( 'multiple' === $file_type ) {
			$name = $name . '[]';
		}

		$html .= '<div class="forminator-field">';

		if ( $label ) {

			if ( $required ) {

				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s %s</label>',
					'forminator-field-' . $id,
					$label,
					forminator_get_required_icon()
				);
			} else {

				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s</label>',
					'forminator-field-' . $id,
					$label
				);
			}
		}

		$file_limit_type  = self::get_property( 'file-limit', $field, 'unlimited' );
		$custom_file_type = self::get_property( 'custom-files', $field, false );
		$custom_file_type = filter_var( $custom_file_type, FILTER_VALIDATE_BOOLEAN );
		$file_mime_types  = $this->file_mime_type( $field );
		$mime_types       = array_filter( $file_mime_types );

		if ( 'multiple' === $file_type ) {
			$upload_method = self::get_property( 'upload-method', $field, 'ajax' );
			$upload_attr   = array(
				'multiple'    => 'multiple',
				'data-method' => $upload_method,
			);
			if ( $custom_file_type ) {
				$upload_attr['accept'] = str_replace( '|', ',.', implode( ',', preg_filter( '/^/', '.', $mime_types ) ) );
			}
			if ( 'custom' === $file_limit_type ) {
				$file_limit                        = self::get_property( 'file-limit-input', $field, 5 );
				$upload_attr['data-limit']         = $file_limit;
				$upload_attr['data-limit-message'] = sprintf( __( 'You can upload a maximum of %d files.', 'forminator' ), $file_limit );
			}
			$upload_limit = self::get_property( 'upload-limit', $field, self::FIELD_PROPERTY_VALUE_NOT_EXIST );
			$max_size     = wp_max_upload_size();
			if ( ! empty( $upload_limit ) ) {
				$filesize  = self::get_property( 'filesize', $field, 'MB' );
				$file_size = $this->file_size( $filesize );
				$max_size  = $upload_limit * $file_size;
			}
			$upload_attr['data-size']         = $max_size;
			$rounded_max_size                 = $this->byte_to_size( $max_size );
			$upload_attr['data-size-message'] = sprintf( __( 'Maximum file size allowed is %1$s. ', 'forminator' ), $rounded_max_size );
			if ( $custom_file_type ) {
				$upload_attr['data-filetype']         = implode( '|', array_values( $mime_types ) );
				$upload_attr['data-filetype-message'] = __( 'file extension is not allowed.', 'forminator' );
			}

			$html .= self::create_file_upload(
				$id,
				$name,
				$description,
				$required,
				$design,
				$file_type,
				$form_id,
				$upload_attr
			);
		} else {
			$upload_attr = array();
			if ( ! empty( $mime_types ) ) {
				$upload_attr['accept'] = str_replace( '|', ',.', implode( ',', preg_filter( '/^/', '.', $mime_types ) ) );
			}

			$html .= self::create_file_upload(
				$id,
				$name,
				$description,
				$required,
				$design,
				$file_type,
				$form_id,
				$upload_attr
			);
		}

		if ( 'multiple' === $file_type ) {
			$html .= sprintf( '<ul class="forminator-uploaded-files upload-container-%s"></ul>', $id );
		}

		$html .= '</div>';

		return apply_filters( 'forminator_field_file_markup', $html, $field );
	}

	/**
	 * Field back-end validation
	 *
	 * @since 1.0
	 *
	 * @param array        $field
	 * @param array|string $data
	 */
	public function validate( $field, $data ) {
		if ( $this->is_required( $field ) ) {
			$id               = self::get_property( 'element_id', $field );
			$required_message = self::get_property( 'required_message', $field, '' );
			if ( empty( $data ) ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_upload_field_required_validation_message',
					( ! empty( $required_message ) ? $required_message : __( 'This field is required. Please upload a file.', 'forminator' ) ),
					$id,
					$field
				);
			}
		}
	}

	/**
	 * Return field inline validation rules
	 * Workaround for actually input file is hidden, so its not accessible via standar html5 `required` attribute
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_validation_rules() {
		$field            = $this->field;
		$id               = self::get_property( 'element_id', $field );
		$file_type        = self::get_property( 'file-type', $field, 'single' );
		$custom_file_type = self::get_property( 'custom-files', $field, false );
		$custom_file_type = filter_var( $custom_file_type, FILTER_VALIDATE_BOOLEAN );
		$element_id       = $this->get_id( $field );
		if ( 'multiple' === $file_type ) {
			$element_id .= '[]';
		}
		$rules              = '"' . $element_id . '": {' . "\n";
		$mime_type          = $this->file_mime_type( $field );
		$allowed_mime_types = ! empty( $mime_type ) ? implode( '|', array_values( $mime_type ) ) : '';

		if ( $this->is_required( $field ) ) {
			$rules .= '"required": true,';
		}

		if ( 'multiple' !== $file_type && $custom_file_type ) {
			$rules .= '"extension": "' . $allowed_mime_types . '",';
		}

		$rules .= '},' . "\n";

		return apply_filters( 'forminator_field_file_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation messages
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_validation_messages() {
		$field       = $this->field;
		$id          = $this->get_id( $field );
		$is_required = $this->is_required( $field );
		$file_type   = self::get_property( 'file-type', $field, 'single' );
		if ( 'multiple' === $file_type ) {
			$id .= '[]';
		}
		$messages = '"' . $id . '": {' . "\n";

		if ( $is_required ) {
			$settings_required_message = self::get_property( 'required_message', $field, '' );
			$required_message          = apply_filters(
				'forminator_upload_field_required_validation_message',
				( ! empty( $settings_required_message ) ? $settings_required_message : __( 'This field is required. Please upload a file.', 'forminator' ) ),
				$id,
				$field
			);
			$messages                  = $messages . '"required": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
		}
		$extension_message = __( 'Error saving form. Uploaded file extension is not allowed.', 'forminator' );
		$messages         .= '"extension": "' . $extension_message . '",' . "\n";

		$messages .= '},' . "\n";

		return $messages;
	}

	/**
	 * Handle file uplload
	 *
	 * @since 1.6 copied from Forminator_Front_Action
	 *
	 * @param array field settings
	 * @param array                $post_data submitted data.
	 * @param string               $upload_type upload type.
	 * @param array                $file_input
	 *
	 * @return bool|array
	 */
	public function handle_file_upload( $field, $post_data = array(), $upload_type = 'submit', $file_input = array() ) {

		$this->field       = $field;
		$id                = self::get_property( 'element_id', $field );
		$field_name        = $id;
		$custom_limit_size = true;
		$upload_limit      = self::get_property( 'upload-limit', $field, self::FIELD_PROPERTY_VALUE_NOT_EXIST );
		$filesize          = self::get_property( 'filesize', $field, 'MB' );
		$custom_file_type  = self::get_property( 'custom-files', $field, false );
		$use_library       = self::get_property( 'use_library', $field, false );
		$file_type         = self::get_property( 'file-type', $field, 'single' );
		$use_library       = filter_var( $use_library, FILTER_VALIDATE_BOOLEAN );
		$mime_types        = array();

		if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST === $upload_limit || empty( $upload_limit ) ) {
			$custom_limit_size = false;
		}

		$custom_file_type = filter_var( $custom_file_type, FILTER_VALIDATE_BOOLEAN );
		if ( $custom_file_type ) {
			// check custom mime.
			$filetypes           = self::get_property( 'filetypes', $field, array(), 'array' );
			$additional          = str_replace( '.', '', self::get_property( 'additional-type', $field, '', 'string' ) );
			$additional_filetype = array_map( 'trim', explode( ',', $additional ) );
			$allFiletype         = array_merge( $filetypes, $additional_filetype );
			foreach ( $allFiletype as $filetype ) {
				// Mime type format = Key is the file extension with value as the mime type.
				$mime_types[ $filetype ] = $filetype;
			}
		}

		$file_object = array();
		if ( ! empty( $file_input ) ) {
			$file_object = $file_input;
		} elseif ( isset( $_FILES[ $field_name ] ) ) {
			$file_object = $_FILES[ $field_name ];
		}
		if ( ! empty( $file_object ) ) {
			if ( isset( $file_object['name'] ) && ! empty( $file_object['name'] ) ) {
				$file_name = sanitize_file_name( $file_object['name'] );

				/**
				 * Filter mime types to be used as validation
				 *
				 * @since 1.6
				 *
				 * @param array $mime_types return null/empty array to use default WP file types @see https://codex.wordpress.org/Plugin_API/Filter_Reference/upload_mimes.
				 * @param array $field
				 */
				$mime_types     = apply_filters( 'forminator_upload_field_mime_types', $mime_types, $field );
				$valid          = wp_check_filetype( $file_name, $mime_types );
				$ext            = pathinfo( $file_name, PATHINFO_EXTENSION );
				$file_base_name = pathinfo( $file_name, PATHINFO_FILENAME );

				$i                  = 1;
				$original_file_name = $file_base_name;
				while ( file_exists( forminator_upload_root() . '/' . $file_base_name . '.' . $ext ) ) {
					$file_base_name = (string) $original_file_name . $i;
					$file_name      = $file_base_name . '.' . $ext;
					$i ++;
				}

				if ( false === $valid['ext'] ) {
					if ( 'multiple' === $file_type ) {
						return array(
							'success' => false,
							'message' => sprintf( __( '.%1$s file extension is not allowed.', 'forminator' ), $ext ),
						);
					} else {
						return array(
							'success' => false,
							'message' => __( 'Error saving form. Uploaded file extension is not allowed.', 'forminator' ),
						);
					}
				}

				$allow = apply_filters( 'forminator_file_upload_allow', true, $field_name, $file_name, $valid );
				if ( false === $allow ) {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Uploaded file extension is not allowed.', 'forminator' ),
					);
				}

				if ( ! is_uploaded_file( $file_object['tmp_name'] ) ) {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Failed to read uploaded file.', 'forminator' ),
					);
				}

				$upload_dir = wp_upload_dir(); // Set upload folder.

				if ( 'upload' === $upload_type && 'multiple' === $file_type ) {
					$file_path = forminator_upload_root();
					$file_url  = formninator_upload_url_root();
				} else {
					$file_path = $upload_dir['path'];
					$file_url  = $upload_dir['url'];
				}

				$unique_file_name = wp_unique_filename( $file_path, $file_name );
				$exploded_name    = explode( '/', $unique_file_name );
				$filename         = end( $exploded_name ); // Create base file name.

				$max_size  = wp_max_upload_size();
				$file_size = $this->file_size( $filesize );
				if ( $custom_limit_size ) {
					$max_size = $upload_limit * $file_size; // convert to byte.
				}

				if ( 0 === $file_object['size'] ) {
					return array(
						'success' => false,
						'message' => __( 'The attached file is empty and can\'t be uploaded.', 'forminator' ),
					);
				}

				if ( $file_object['size'] > $max_size ) {

					$rounded_max_size = $this->byte_to_size( $max_size );

					return array(
						'success' => false,
						'message' => sprintf( /* translators: ... */
							__( 'Maximum file size allowed is %1$s. ', 'forminator' ),
							$rounded_max_size
						),
					);
				}

				if ( UPLOAD_ERR_OK !== $file_object['error'] ) {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Upload error. ', 'forminator' ),
					);
				}

				if ( ! is_dir( $file_path ) ) {
					wp_mkdir_p( $file_path );
				}

				if ( wp_is_writable( $file_path ) ) {
					$file_path = $file_path . '/' . $filename;
					$file_url  = $file_url . '/' . $filename;
				} else {
					$file_path = $upload_dir['basedir'] . '/' . $filename;
					$file_url  = $upload_dir['baseurl'] . '/' . $filename;
				}

				if ( 'multiple' === $file_type ) {
					$file_limit_type = self::get_property( 'file-limit', $field, 'unlimited' );
					if ( 'custom' === $file_limit_type ) {
						$file_limit = self::get_property( 'file-limit-input', $field, 5 );
						if ( isset( $post_data['totalFiles'] ) && $post_data['totalFiles'] > $file_limit ) {
							if ( 'upload' === $upload_type ) {
								move_uploaded_file( $file_object['tmp_name'], $file_path );
							}

							return array(
								'error_type' => 'limit',
								'success'    => false,
								'message'    => sprintf( __( 'You can upload a maximum of %d files.', 'forminator' ), $file_limit ),
							);
						}
					}
				}

				$file_mime = $this->get_mime_type( $file_object['tmp_name'] );
				// use move_uploaded_file instead of $wp_filesystem->put_contents.
				// increase performance, and avoid permission issues.
				if ( false !== move_uploaded_file( $file_object['tmp_name'], $file_path ) ) {
					if ( $use_library && ( 'multiple' !== $file_type || ( 'multiple' === $file_type && 'submit' === $upload_type ) ) ) {
						$upload_id = wp_insert_attachment(
							array(
								'guid'           => $file_path,
								'post_mime_type' => $file_mime,
								'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
								'post_content'   => '',
								'post_status'    => 'inherit',
							),
							$file_path
						);

						// wp_generate_attachment_metadata() won't work if you do not include this file.
						require_once ABSPATH . 'wp-admin/includes/image.php';

						// Generate and save the attachment metas into the database.
						wp_update_attachment_metadata( $upload_id, wp_generate_attachment_metadata( $upload_id, $file_path ) );
					}

					return array(
						'success'   => true,
						'file_url'  => $file_url,
						'message'   => '',
						'file_path' => $file_path,
					);

				} else {
					return array(
						'success' => false,
						'message' => __( 'Error saving form. Upload error. ', 'forminator' ),
					);
				}
			}
		}

		return false;
	}

	/**
	 * Handle multiple file upload with ajax
	 *
	 * @since 1.6 copied from Forminator_Front_Action
	 *
	 * @param array $upload_data settings.
	 * @param array $field_array field array.
	 *
	 * @return bool|array
	 */
	public function handle_ajax_multifile_upload( $upload_data, $field_array = array() ) {
		$file_path_arr = array();
		$file_url_arr  = array();
		$use_library   = self::get_property( 'use_library', $field_array, false );
		$file_type     = self::get_property( 'file-type', $field_array, 'single' );
		if ( ! empty( $upload_data ) ) {
			if ( false !== array_search( false, array_column( $upload_data, 'success' ) ) ) {
				return array(
					'success' => false,
				);
			}
			$upload_dir = wp_upload_dir();
			foreach ( $upload_data as $upload ) {
				if ( ! empty( $upload ) ) {
					$file_name = trim( sanitize_file_name( $upload['file_name'] ) );
					$temp_path = forminator_upload_root() . '/' . $file_name;

					$unique_file_name = wp_unique_filename( $upload_dir['path'], $file_name );
					$exploded_name    = explode( '/', $unique_file_name );
					$filename         = end( $exploded_name );
					if ( wp_is_writable( $upload_dir['path'] ) ) {
						$file_path = $upload_dir['path'] . '/' . trim( sanitize_file_name( $filename ) );
						$file_url  = $upload_dir['url'] . '/' . trim( sanitize_file_name( $filename ) );
					} else {
						$file_path = $upload_dir['basedir'] . '/' . trim( sanitize_file_name( $filename ) );
						$file_url  = $upload_dir['baseurl'] . '/' . trim( sanitize_file_name( $filename ) );
					}

					if ( file_exists( $temp_path ) ) {
						if ( rename( $temp_path, $file_path ) ) {
							if ( $use_library && 'multiple' === $file_type ) {
								$upload_id = wp_insert_attachment(
									array(
										'guid'           => $file_path,
										'post_mime_type' => $upload['mime_type'],
										'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
										'post_content'   => '',
										'post_status'    => 'inherit',
									),
									$file_path
								);

								// wp_generate_attachment_metadata() won't work if you do not include this file.
								require_once ABSPATH . 'wp-admin/includes/image.php';

								// Generate and save the attachment metas into the database.
								wp_update_attachment_metadata( $upload_id, wp_generate_attachment_metadata( $upload_id, $file_path ) );
							}

							$file_path_arr[] = $file_path;
							$file_url_arr[]  = $file_url;
						}
					} else {
						// Check maybe it was already saved on previous submission but it had other fields validation issues.
						preg_match( '/(\-([0-9]+))\.[^.]+$/', $file_path, $matches );
						if ( ! empty( $matches[0] ) ) {
							if ( '-1' === $matches[1] ) {
								$replace = '';
							} else {
								$replace = '-' . ( --$matches[2] );
							}
							$ext       = str_replace( $matches[1], $replace, $matches[0] );
							$file_path = substr( $file_path, 0, -strlen( $matches[0] ) ) . $ext;
							$file_url  = substr( $file_url, 0, -strlen( $matches[0] ) ) . $ext;
							if ( file_exists( $file_path ) ) {
								$file_path_arr[] = $file_path;
								$file_url_arr[]  = $file_url;
							}
						}
					}
				}
			}
			if ( ! empty( $file_url_arr ) && ! empty( $file_path_arr ) ) {

				return array(
					'success'   => true,
					'file_url'  => $file_url_arr,
					'file_path' => $file_path_arr,
				);
			} else {

				return array(
					'success' => false,
					'message' => __( 'Error saving form. Upload error. ', 'forminator' ),
				);
			}
		}

		return false;
	}

	/**
	 * Handle multiple file upload with submission
	 *
	 * @since 1.6 copied from Forminator_Front_Action
	 *
	 * @param array $field
	 * @param array $upload_data settings.
	 *
	 * @return bool|array
	 */
	public function handle_submission_multifile_upload( $field, $upload_data ) {
		$file_path_arr = array();
		$file_url_arr  = array();
		if ( ! empty( $upload_data ) ) {
			$upload_file = $this->arrange_files( $upload_data );
			$i           = 1;
			foreach ( $upload_file as $upload ) {
				$response = $this->handle_file_upload( $field, array( 'totalFiles' => $i ), 'submit', $upload );
				if ( isset( $response['success'] ) && $response['success'] ) {
					$file_path_arr[] = $response['file_path'];
					$file_url_arr[]  = $response['file_url'];
				} else {
					return $response;
				}

				$i++;
			}
			if ( ! empty( $file_url_arr ) && ! empty( $file_path_arr ) ) {

				return array(
					'success'   => true,
					'file_url'  => $file_url_arr,
					'file_path' => $file_path_arr,
				);
			} else {
				return array(
					'success' => false,
					'message' => __( 'Error saving form. Upload error. ', 'forminator' ),
				);
			}
		}

		return false;
	}

	/**
	 * File size
	 *
	 * @param $file_size
	 *
	 * @return mixed
	 */
	public function file_size( $file_size ) {

		switch ( $file_size ) {
			case 'KB':
				$size = 1000;
				break;
			case 'B':
				$size = 1;
				break;
			default:
				$size = 1000000;
				break;
		}

		return $size;
	}

	/**
	 * @param $files
	 *
	 * @return array
	 */
	public function arrange_files( $files ) {
		$new = array();
		foreach ( $files as $key => $file ) {
			foreach ( $file as $i => $val ) {
				$new[ $i ][ $key ] = $val;
			}
		}

		return $new;
	}

	/**
	 * Byte to size
	 *
	 * @param $size
	 *
	 * @return float|string
	 */
	public function byte_to_size( $size ) {
		$rounded_max_size = round( $size / 1000000 );

		if ( $rounded_max_size <= 0 ) {
			// go to KB.
			$rounded_max_size = round( $size / 1000 );

			if ( $rounded_max_size <= 0 ) {
				// go to B.
				$rounded_max_size = round( $size ) . ' B';
			} else {
				$rounded_max_size .= ' KB';
			}
		} else {
			$rounded_max_size .= ' MB';
		}

		return $rounded_max_size;
	}

	/**
	 * Get all Filetypes
	 *
	 * @param $field
	 *
	 * @return array
	 */
	public function file_mime_type( $field ) {
		$mime_types          = array();
		$default_all         = array(
			'all-image',
			'all-video',
			'all-document',
			'all-audio',
			'all-archive',
			'all-text',
			'all-spreadsheet',
			'all-interactive',
		);
		$filetypes           = self::get_property( 'filetypes', $field, array(), 'array' );
		$file_types          = array_diff( array_merge( $default_all, $filetypes ), $default_all );
		$additional          = str_replace( '.', '', self::get_property( 'additional-type', $field, '', 'string' ) );
		$additional_filetype = array_map( 'trim', explode( ',', $additional ) );
		$allFiletype         = array_merge( $file_types, $additional_filetype );
		if ( ! empty( $allFiletype ) ) {
			foreach ( $allFiletype as $filetype ) {
				$mime_types[ $filetype ] = $filetype;
			}
		}

		return $mime_types;
	}

	/**
	 * Get mime type, provide alternative if function is not available
	 *
	 * @param $file
	 *
	 * @return string
	 */
	public function get_mime_type( $file ) {
		if ( function_exists( 'mime_content_type' ) ) {
			$mime_type = mime_content_type( $file );
		} else {
			$file_type = wp_check_filetype( $file );
			$mime_type = $file_type['type'];
		}

		return $mime_type;
	}

	/**
	 * Set permission
	 *
	 * @param $path
	 */
	public function set_permissions( $path ) {
		$permission = apply_filters( 'forminator_file_permission', 0755, $path );
		if ( $permission ) {
			@chmod( $path, $permission );
		}
	}
}
