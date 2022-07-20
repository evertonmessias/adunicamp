<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_Addons_page
 *
 * @since 1.15
 */
class Forminator_Admin_Addons_page {

	/**
	 * Plugin instance
	 *
	 * @since  1.11
	 * @access private
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance
	 *
	 * @return Forminator_Admin_Addons_page|null
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Addons action ajax
	 *
	 * @param $action
	 */
	public function addons_action_ajax( $action ) {
		$pid        = intval( Forminator_Core::sanitize_text_field( 'pid' ) );
		$is_network = 1 === intval( Forminator_Core::sanitize_text_field( 'is_network' ) );

		switch ( $action ) {
			case 'addons-install':
				if ( $pid ) {
					if ( WPMUDEV_Dashboard::$upgrader->user_can_install( $pid ) ) {
						$installed = WPMUDEV_Dashboard::$upgrader->is_project_installed( $pid );
						if ( ! $installed ) {
							$success = WPMUDEV_Dashboard::$upgrader->install( $pid );
							if ( $success ) {
								$html_addons = $this->addons_html( $pid );
								wp_send_json_success(
									array(
										'message' => sprintf( __( '%s add-on was successfully installed', 'forminator' ), $this->get_addon_value( $pid, 'name' ) ),
										'html'    => $html_addons,
									)
								);
							}
						}
					}
				}
				$err = WPMUDEV_Dashboard::$upgrader->get_error();
				wp_send_json_error(
					array(
						'error' => $err,
					)
				);
				break;
			case 'addons-activate':
				if ( $pid ) {
					$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
					if ( empty( $local ) ) {
						$errors['error'] = array(
							'message' => __( 'Not installed', 'forminator' ),
						);
						wp_send_json_error( $errors );
					}

					$result = activate_plugin( $local['filename'], '', $is_network );
					if ( is_wp_error( $result ) ) {
						$errors['error'] = array(
							'file'    => $pid,
							'code'    => $result->get_error_code(),
							'message' => $result->get_error_message(),
						);
						wp_send_json_error( $errors );
					} else {
						WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();
						$html_addons = $this->addons_html( $pid );
						wp_send_json_success(
							array(
								'message' => sprintf( __( '%s add-on was successfully activated', 'forminator' ), $this->get_addon_value( $pid, 'name' ) ),
								'html'    => $html_addons,
							)
						);
					}
				}
				break;
			case 'addons-deactivate':
				if ( $pid ) {
					$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
					if ( empty( $local ) ) {
						$errors['error'] = array(
							'message' => __( 'Not installed', 'forminator' ),
						);
						wp_send_json_error( $errors );
					}

					// Check that it's a valid plugin.
					$valid = validate_plugin( $local['filename'] );
					if ( is_wp_error( $valid ) ) {
						$errors['error'] = array(
							'file'    => $pid,
							'code'    => $valid->get_error_code(),
							'message' => $valid->get_error_message(),
						);
						wp_send_json_error( $errors );
					}

					deactivate_plugins( $local['filename'], false, $is_network );
					// there is no return so we always call it a success.
					WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();
					$html_addons = $this->addons_html( $pid );
					wp_send_json_success(
						array(
							'message' => sprintf( __( '%s add-on was successfully deactivated', 'forminator' ), $this->get_addon_value( $pid, 'name' ) ),
							'html'    => $html_addons,
						)
					);
				}
				break;
			case 'addons-delete':
				if ( $pid ) {
					if ( WPMUDEV_Dashboard::$upgrader->delete_plugin( $pid ) ) {
						$html_addons = $this->addons_html( $pid );
						wp_send_json_success(
							array(
								'message' => sprintf( __( '%s add-on was successfully deleted', 'forminator' ), $this->get_addon_value( $pid, 'name' ) ),
								'html'    => $html_addons,
							)
						);
					} else {
						$err = WPMUDEV_Dashboard::$upgrader->get_error();
						wp_send_json_error( $err );
					}
				}
				break;
			case 'addons-update':
				if ( $pid ) {
					$success = WPMUDEV_Dashboard::$upgrader->upgrade( $pid );

					if ( ! $success ) {
						$error           = WPMUDEV_Dashboard::$upgrader->get_error();
						$errors['error'] = array(
							'message' => $error['message'],
						);
						wp_send_json_error( $errors );
					}

					$html_addons = $this->addons_html( $pid );
					wp_send_json_success(
						array(
							'message' => sprintf( __( '%s add-on was successfully updated', 'forminator' ), $this->get_addon_value( $pid, 'name' ) ),
							'html'    => $html_addons,
						)
					);
				}
				break;
			default:
				wp_send_json_error(
					array(
						'message' => sprintf(
							__( 'Unknown action: %s', 'forminator' ),
							esc_html( $action )
						),
					)
				);
				break;
		}
	}

	/**
	 * Render addons content
	 *
	 * @param $name
	 * @param $pid
	 * @param $addons
	 */
	public function addons_render( $name, $pid, $addons = array() ) {

		$file_name = $name . '.php';

		$file_path = forminator_plugin_dir() . 'admin/views/addons/' . $file_name;

		$path = false;
		if ( file_exists( $file_path ) ) {
			$path = $file_path;
		}

		if ( $path ) {

			if ( empty( $addons ) ) {
				$addons = $this->get_addons( $pid );
			}

			/**
			 * Output some content before the template is loaded, or modify the
			 * variables passed to the file.
			 *
			 * @var  array $data The
			 */
			$new_data = apply_filters( 'forminator_before-' . $name, $addons );
			if ( isset( $new_data ) && is_array( $new_data ) ) {
				$addons = $new_data;
			}

			require $path;

			/**
			 * Output code or do stuff after the template was loaded.
			 */
			do_action( 'forminator_after-' . $name );
		} else {
			printf(
				'<div class="error"><p>%s</p></div>',
				sprintf(
					esc_html__( 'Error: The file %s does not exist. Please re-install the plugin.', 'forminator' ),
					'"' . esc_html( $name ) . '"'
				)
			);
		}
	}

	/**
	 * Get addon
	 *
	 * @param $pid
	 *
	 * @return array|false|object
	 */
	public function get_addons( $pid ) {
		$addon = array();
		if ( $pid ) {
			$dash  = WPMUDEV_Dashboard::instance();
			$addon = $dash::$site->get_project_info( $pid );
		}

		return $addon;
	}

	/**
	 * Get addon value
	 *
	 * @param $pid
	 * @param $key
	 *
	 * @return string
	 */
	public function get_addon_value( $pid, $key ) {
		$value = '';
		$addon = $this->get_addons( $pid );
		if ( ! empty( $addon ) ) {
			$value = isset( $addon->{$key} ) ? $addon->{$key} : '';
		}

		return $value;
	}

	/**
	 * Get addons html
	 *
	 * @param $pid
	 *
	 * @return string
	 */
	public function addons_html( $pid ) {
		ob_start();
		$this->addons_render( 'addons-list', $pid );

		return ob_get_clean();
	}

	/**
	 * Renders a view file with static call.
	 *
	 * @since 1.0
	 * @since 4.2.0 Moved from Opt_In to this class.
	 *
	 * @param string     $file Path to the view file.
	 * @param array      $params Array whose keys will be variable names when within the view file.
	 * @param bool|false $return Whether to echo or return the contents.
	 * @return string
	 */
	public function render_template( $file, $params = array(), $return = false ) {

		// Assign $file to a variable which is unlikely to be used by users of the method.
		extract( $params, EXTR_OVERWRITE ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		if ( $return ) {
			ob_start();
		}

		$file_name = $file . '.php';

		$file_path = forminator_plugin_dir() . $file_name;
		if ( file_exists( $file_path ) ) {
			include $file_path;
		}

		if ( $return ) {
			return ob_get_clean();
		}

		if ( ! empty( $params ) ) {
			foreach ( $params as $param ) {
				unset( $param );
			}
		}
	}

	/**
	 * Get addon by id
	 *
	 * @param $pid
	 *
	 * @return false|object|stdClass
	 */
	public static function forminator_addon_by_pid( $pid ) {
		$res = array();
		if ( class_exists( 'WPMUDEV_Dashboard' ) ) {
			$res = WPMUDEV_Dashboard::$site->get_project_info( $pid, true );
		} else {
			$addons = self::forminator_get_static_addon();
			foreach ( $addons as $addon ) {
				if ( $pid === $addon->pid ) {
					$res = $addon;
				}
			}
		}

		return $res;
	}

	/**
	 * Get static addon
	 *
	 * @return stdClass[]
	 */
	public static function forminator_get_static_addon() {
		$stripe_addon                    = new stdClass();
		$stripe_addon->pid               = 3953609;
		$stripe_addon->name              = esc_html__( 'Forminator Stripe Subscriptions Add-on', 'forminator' );
		$stripe_addon->info              = esc_html__( 'The Stripe subscription add-on lets you collect recurring/subscription payments with Forminator Pro on your WordPress sites. You can also create a Stripe subscription payment plans directly from Forminator Pro, and use conditional logic to process any of your payment fields based on your form field values.', 'forminator' );
		$stripe_addon->version_latest    = '1.0';
		$stripe_addon->version_installed = '1.0';
		$stripe_addon->is_network_admin  = is_network_admin();
		$stripe_addon->is_hidden         = false;
		$stripe_addon->features          = array(
			esc_html__( 'Create and manage one-time and recurring Stripe payments in Forminator Pro.', 'forminator' ),
			esc_html__( 'Setup products in Forminator within minutes.', 'forminator' ),
			esc_html__( 'Offer users a trial period for your product before they start paying.', 'forminator' ),
			esc_html__( 'Use conditional logic to process payments based on form input field values.', 'forminator' ),
		);
		$stripe_addon->url               = (object) array(
			'thumbnail' => esc_url( forminator_plugin_url() . 'assets/images/forminator-stripe-logo.png' ),
		);
		$stripe_addon->changelog         = array(
			array(
				'time'    => '1628782583',
				'version' => '1.0',
				'log'     => '<p>- First public release</p>',
			),
		);

		return array( $stripe_addon );
	}
}
