<?php
/**
 * ACF field registrations, plus the resolver the templates actually call.
 *
 * ACF is the project's field system, but it is a plugin and can be absent —
 * on a fresh install, or if it is deactivated. Rather than fatal on
 * get_field(), awaqi_field() falls back to the Customizer values registered in
 * functions.php, so the theme renders correctly either way.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether ACF is active.
 *
 * @return bool
 */
function awaqi_has_acf() {
	return function_exists( 'get_field' ) && function_exists( 'acf_add_local_field_group' );
}

/**
 * Reads a theme field: ACF first, Customizer second, registered default last.
 *
 * @param string $key     Field name, shared between ACF and the Customizer.
 * @param string $default Fallback value.
 * @return string
 */
function awaqi_field( $key, $default = '' ) {
	if ( awaqi_has_acf() ) {
		// Options-page values live on the 'option' post ID.
		$value = get_field( $key, 'option' );

		if ( ! empty( $value ) && is_string( $value ) ) {
			return $value;
		}
	}

	return awaqi_option( $key, $default );
}

/**
 * Registers the options page that holds the scene and hero fields.
 */
function awaqi_acf_options_page() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page( array(
		'page_title' => __( 'Awaqi — Scene & Hero', 'awaqi' ),
		'menu_title' => __( 'Scene & Hero', 'awaqi' ),
		'menu_slug'  => 'awaqi-scene',
		'capability' => 'edit_theme_options',
		'icon_url'   => 'dashicons-format-image',
		'position'   => 59,
		'redirect'   => false,
	) );
}
add_action( 'acf/init', 'awaqi_acf_options_page' );

/**
 * Registers the field group in code, so fields travel with the theme in git
 * rather than living only in the database.
 */
function awaqi_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'      => 'group_awaqi_scene',
		'title'    => __( 'Scene & Hero', 'awaqi' ),
		'location' => array(
			array(
				array(
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'awaqi-scene',
				),
			),
		),
		'fields' => array(
			array(
				'key'           => 'field_awaqi_scene_url',
				'label'         => __( 'Spline scene URL', 'awaqi' ),
				'name'          => 'awaqi_scene_url',
				'type'          => 'url',
				'instructions'  => __( 'Public share URL from Spline.', 'awaqi' ),
				'default_value' => AWAQI_DEFAULT_SCENE,
			),
			array(
				'key'           => 'field_awaqi_hero_heading',
				'label'         => __( 'Hero heading', 'awaqi' ),
				'name'          => 'awaqi_hero_heading',
				'type'          => 'textarea',
				'rows'          => 2,
				'instructions'  => __( 'Each new line becomes a line break on desktop.', 'awaqi' ),
				'default_value' => "Intelligence that\nmoves with you.",
			),
			array(
				'key'           => 'field_awaqi_hero_text',
				'label'         => __( 'Hero paragraph', 'awaqi' ),
				'name'          => 'awaqi_hero_text',
				'type'          => 'textarea',
				'rows'          => 3,
				'default_value' => 'Awaqi AI brings context, memory, and real-time reasoning to the device already in your pocket.',
			),
			array(
				'key'           => 'field_awaqi_cta_label',
				'label'         => __( 'Button label', 'awaqi' ),
				'name'          => 'awaqi_cta_label',
				'type'          => 'text',
				'instructions'  => __( 'Leave empty to hide the button.', 'awaqi' ),
				'default_value' => 'Join waitlist',
			),
			array(
				'key'           => 'field_awaqi_cta_url',
				'label'         => __( 'Button link', 'awaqi' ),
				'name'          => 'awaqi_cta_url',
				'type'          => 'text',
				'default_value' => '#waitlist',
			),
			array(
				'key'           => 'field_awaqi_waitlist_heading',
				'label'         => __( 'Waitlist heading', 'awaqi' ),
				'name'          => 'awaqi_waitlist_heading',
				'type'          => 'text',
				'default_value' => 'Be first in line.',
			),
			array(
				'key'           => 'field_awaqi_waitlist_text',
				'label'         => __( 'Waitlist paragraph', 'awaqi' ),
				'name'          => 'awaqi_waitlist_text',
				'type'          => 'textarea',
				'rows'          => 3,
				'default_value' => 'Join the waitlist and we’ll let you know as soon as Awaqi goes live.',
			),
			array(
				'key'           => 'field_awaqi_waitlist_button',
				'label'         => __( 'Waitlist button label', 'awaqi' ),
				'name'          => 'awaqi_waitlist_button',
				'type'          => 'text',
				'default_value' => 'Join waitlist',
			),
			array(
				'key'           => 'field_awaqi_waitlist_success',
				'label'         => __( 'Success message', 'awaqi' ),
				'name'          => 'awaqi_waitlist_success',
				'type'          => 'text',
				'default_value' => 'You are on the list. We will be in touch.',
			),
		),
	) );
}
add_action( 'acf/init', 'awaqi_acf_fields' );
