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
				'type'          => 'text',
				'default_value' => 'Intelligence that moves with you.',
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
				'key'           => 'field_awaqi_hint_text',
				'label'         => __( 'Interaction hint', 'awaqi' ),
				'name'          => 'awaqi_hint_text',
				'type'          => 'text',
				'instructions'  => __( 'Leave empty to hide it.', 'awaqi' ),
				'default_value' => 'Drag to explore',
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
				'key'           => 'field_awaqi_model_heading',
				'label'         => __( 'Model section heading', 'awaqi' ),
				'name'          => 'awaqi_model_heading',
				'type'          => 'text',
				'instructions'  => __( 'Only shown when a GLB model is bundled with the theme.', 'awaqi' ),
				'default_value' => 'Step inside the corridor.',
			),
			array(
				'key'           => 'field_awaqi_model_text',
				'label'         => __( 'Model section paragraph', 'awaqi' ),
				'name'          => 'awaqi_model_text',
				'type'          => 'textarea',
				'rows'          => 3,
				'default_value' => 'A real-time environment running in your browser — drag to look around, scroll to move through it.',
			),
			array(
				'key'          => 'field_awaqi_model_poster',
				'label'        => __( 'Model poster image URL', 'awaqi' ),
				'name'         => 'awaqi_model_poster',
				'type'         => 'url',
				'instructions' => __( 'Shown while the model loads. Defaults to the bundled render.', 'awaqi' ),
			),
		),
	) );
}
add_action( 'acf/init', 'awaqi_acf_fields' );
