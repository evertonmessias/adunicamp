<?php

/**
 * Class Forminator_Addon_Settings_Abstract
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * @since 1.1
 */
abstract class Forminator_Addon_Settings_Abstract {

	/**
	 * Get HTML select-options
	 *
	 * @param object $options Options.
	 * @param string $selected_value Saved value.
	 * @return string
	 */
	public static function get_select_html( $options, $selected_value = '' ) {
		$html = '<option value="">' . __( 'None', 'forminator' ) . '</option>';

		foreach ( $options as $id => $title ) {
			$html .= '<option value="' . esc_attr( $id ) . '" ' . selected(
				$selected_value,
				$id,
				false
			) . '>' . esc_html( $title ) . '</option>';
		}

		return $html;
	}

	/**
	 * Get HTML checkbox-options
	 *
	 * @param object $options Options.
	 * @param string $name Name attribute.
	 * @param array  $selected_values Saved values.
	 * @return string
	 */
	public static function get_checkboxes_html( $options, $name, $selected_values = array() ) {
		$html = '';

		foreach ( $options as $id => $title ) {
			$html .= '<label for="' . esc_attr( $id ) . '" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked">' .
				'<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="checkbox" value="' . esc_attr( $id ) . '"' .
				checked( is_array( $selected_values ) && in_array( $id, $selected_values, true ), true, false ) .
				'><span aria-hidden="true"></span><span>' . esc_attr( $title ) . '</span></label>';
		}

		return $html;
	}

	/**
	 * Get HTML radio-options
	 *
	 * @param object $options Options.
	 * @param string $name Name attribute.
	 * @param array  $selected_values Saved values.
	 * @return string
	 */
	public static function get_radios_html( $options, $name, $selected_value = '' ) {
		$html = '';

		foreach ( $options as $id => $title ) {
			$html .= '<label for="' . esc_attr( $id ) . '" class="sui-radio sui-radio-sm sui-radio-stacked">' .
				'<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="radio" value="' . esc_attr( $id ) . '"' .
				checked( $id === $selected_value, true, false ) .
				'><span aria-hidden="true"></span><span>' . esc_attr( $title ) . '</span></label>';
		}

		return $html;
	}

	/**
	 * Get HTML for refresh button
	 *
	 * @return string
	 */
	public static function refresh_button() {
		$html = '<button class="sui-button-icon sui-tooltip forminator-refresh-email-lists" data-tooltip="'
				. esc_html__( 'Refresh list', 'forminator' ) . '" type="button">'
				. '<span class="sui-loading-text" aria-hidden="true">'
				. '<i class="sui-icon-refresh"></i>'
				. '</span>'
				. '<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>'
				. '<span class="sui-screen-reader-text">' . esc_html__( 'Refresh', 'forminator' ) . '</span>'
				. '</button>';

		return $html;
	}

	/**
	 * Meta key that will be used to save addon setting on WP post_meta
	 *
	 * @return string
	 */
	final public function get_settings_meta_key() {
		$addon     = $this->addon;
		$global_id = ! empty( $addon->multi_global_id ) ? '_' . $addon->multi_global_id : '';
		return 'forminator_addon_' . $addon->get_slug() . '_' . static::$module_slug . '_settings' . $global_id;
	}
}
