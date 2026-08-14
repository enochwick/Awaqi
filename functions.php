<?php
/**
 * Awaqi theme setup.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

define( 'AWAQI_VERSION', '1.1.0' );

/**
 * The Spline scene the front page renders when nothing is configured.
 */
define( 'AWAQI_DEFAULT_SCENE', 'https://my.spline.design/awaqiaiformobile-petbTiUGXaQcJ56veNakhd4C/' );

require_once get_template_directory() . '/inc/acf-fields.php';
require_once get_template_directory() . '/inc/waitlist.php';

/* ---------------------------------------------------------------------------
 * Setup
 * ------------------------------------------------------------------------ */

/**
 * Theme supports and menu registration.
 */
function awaqi_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array(
		'height'      => 56,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus( array(
		'primary' => __( 'Primary', 'awaqi' ),
	) );

	load_theme_textdomain( 'awaqi', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'awaqi_setup' );

/* ---------------------------------------------------------------------------
 * Assets
 * ------------------------------------------------------------------------ */

/**
 * Front-end styles and scripts. Versioned by file mtime so a deploy busts the
 * cache without anyone having to remember to bump a number.
 */
function awaqi_assets() {
	$dir = get_template_directory();
	$uri = get_template_directory_uri();

	wp_enqueue_style(
		'awaqi',
		$uri . '/assets/css/main.css',
		array(),
		awaqi_asset_version( $dir . '/assets/css/main.css' )
	);

	wp_enqueue_script(
		'awaqi-main',
		$uri . '/assets/js/main.js',
		array(),
		awaqi_asset_version( $dir . '/assets/js/main.js' ),
		true
	);

	if ( awaqi_is_scene() ) {
		wp_enqueue_script(
			'awaqi-scene',
			$uri . '/assets/js/scene.js',
			array(),
			awaqi_asset_version( $dir . '/assets/js/scene.js' ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'awaqi_assets' );

/**
 * File mtime when the file exists, theme version otherwise.
 *
 * @param string $path Absolute path to the asset.
 * @return string
 */
function awaqi_asset_version( $path ) {
	return file_exists( $path ) ? (string) filemtime( $path ) : AWAQI_VERSION;
}

/* ---------------------------------------------------------------------------
 * Conditionals
 * ------------------------------------------------------------------------ */

/**
 * Whether the current view is the immersive 3D scene.
 *
 * @return bool
 */
function awaqi_is_scene() {
	return is_front_page() && ! is_paged();
}

/**
 * Body classes.
 *
 * @param array $classes Body classes.
 * @return array
 */
function awaqi_body_class( $classes ) {
	if ( awaqi_is_scene() ) {
		// The waitlist sits below the scene, so the front page always scrolls.
		$classes[] = 'is-scene';
		$classes[] = 'is-scene-scroll';
	}

	return $classes;
}
add_filter( 'body_class', 'awaqi_body_class' );

/* ---------------------------------------------------------------------------
 * Customizer — fallback field storage when ACF is not installed
 * ------------------------------------------------------------------------ */

/**
 * Field definitions, shared by the Customizer and inc/acf-fields.php.
 *
 * @return array
 */
function awaqi_fields() {
	return array(
		'awaqi_scene_url' => array(
			'default'  => AWAQI_DEFAULT_SCENE,
			'label'    => __( 'Spline scene URL', 'awaqi' ),
			'type'     => 'url',
			'sanitize' => 'esc_url_raw',
			'help'     => __( 'Paste the public share URL from Spline.', 'awaqi' ),
		),
		'awaqi_hero_heading' => array(
			'default'  => __( 'Intelligence that moves with you.', 'awaqi' ),
			'label'    => __( 'Hero heading', 'awaqi' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'awaqi_hero_text' => array(
			'default'  => __( 'Awaqi AI brings context, memory, and real-time reasoning to the device already in your pocket.', 'awaqi' ),
			'label'    => __( 'Hero paragraph', 'awaqi' ),
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		),
		'awaqi_hint_text' => array(
			'default'  => __( 'Drag to explore', 'awaqi' ),
			'label'    => __( 'Interaction hint', 'awaqi' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
			'help'     => __( 'Leave empty to hide it.', 'awaqi' ),
		),
		'awaqi_cta_label' => array(
			'default'  => __( 'Join waitlist', 'awaqi' ),
			'label'    => __( 'Button label', 'awaqi' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
			'help'     => __( 'Leave empty to hide the button.', 'awaqi' ),
		),
		'awaqi_cta_url' => array(
			'default'  => '#waitlist',
			'label'    => __( 'Button link', 'awaqi' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'awaqi_waitlist_heading' => array(
			'default'  => __( 'Be first through the door.', 'awaqi' ),
			'label'    => __( 'Waitlist heading', 'awaqi' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'awaqi_waitlist_text' => array(
			'default'  => __( 'Join the waitlist and we will let you know the moment Awaqi opens up.', 'awaqi' ),
			'label'    => __( 'Waitlist paragraph', 'awaqi' ),
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		),
		'awaqi_waitlist_button' => array(
			'default'  => __( 'Join waitlist', 'awaqi' ),
			'label'    => __( 'Waitlist button label', 'awaqi' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'awaqi_waitlist_success' => array(
			'default'  => __( 'You are on the list. We will be in touch.', 'awaqi' ),
			'label'    => __( 'Success message', 'awaqi' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
	);
}

/**
 * Registers the Customizer panel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function awaqi_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'awaqi_scene', array(
		'title'       => __( 'Awaqi — Scene & Hero', 'awaqi' ),
		'priority'    => 25,
		'description' => __( 'Controls the interactive 3D scene and the copy layered over it. When ACF is active, its Scene & Hero options page takes precedence.', 'awaqi' ),
	) );

	foreach ( awaqi_fields() as $id => $field ) {
		$wp_customize->add_setting( $id, array(
			'default'           => $field['default'],
			'sanitize_callback' => $field['sanitize'],
			'transport'         => 'refresh',
		) );

		$wp_customize->add_control( $id, array(
			'section'     => 'awaqi_scene',
			'label'       => $field['label'],
			'type'        => $field['type'],
			'description' => isset( $field['help'] ) ? $field['help'] : '',
		) );
	}
}
add_action( 'customize_register', 'awaqi_customize_register' );

/**
 * Reads a Customizer value, falling back to its registered default.
 *
 * Templates should call awaqi_field() instead — it checks ACF first.
 *
 * @param string $key     Option key.
 * @param string $default Fallback value.
 * @return string
 */
function awaqi_option( $key, $default = '' ) {
	if ( '' === $default ) {
		$fields = awaqi_fields();

		if ( isset( $fields[ $key ]['default'] ) ) {
			$default = $fields[ $key ]['default'];
		}
	}

	$value = get_theme_mod( $key, $default );

	return is_string( $value ) ? $value : $default;
}

/* ---------------------------------------------------------------------------
 * Template helpers
 * ------------------------------------------------------------------------ */

/**
 * Renders the site brand — custom logo when set, wordmark otherwise.
 */
function awaqi_brand() {
	if ( has_custom_logo() ) {
		// Reuse the brand class so spacing matches the wordmark variant.
		echo str_replace( 'custom-logo-link', 'custom-logo-link brand', get_custom_logo() ); // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}

	$name  = get_bloginfo( 'name' );
	$parts = explode( ' ', $name, 2 );
	?>
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<?php echo esc_html( $parts[0] ); ?>
		<?php if ( isset( $parts[1] ) ) : ?>
			<span class="brand__suffix">&nbsp;<?php echo esc_html( $parts[1] ); ?></span>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * Primary nav with the CTA button appended. Falls back to the CTA alone when
 * no menu has been assigned, so a fresh install still looks intentional.
 */
function awaqi_nav() {
	$cta = awaqi_cta_item( awaqi_field( 'awaqi_cta_label' ), awaqi_field( 'awaqi_cta_url' ) );
	?>
	<nav class="nav" aria-label="<?php esc_attr_e( 'Primary', 'awaqi' ); ?>">
		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => '',
				'depth'          => 1,
				'items_wrap'     => '<ul>%3$s' . $cta . '</ul>',
				'fallback_cb'    => false,
			) );
		} else {
			echo '<ul>' . $cta . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</nav>
	<?php
}

/**
 * Markup for the CTA list item, or an empty string when it has no label.
 *
 * @param string $label Button label.
 * @param string $url   Button link.
 * @return string
 */
function awaqi_cta_item( $label, $url ) {
	if ( '' === trim( $label ) ) {
		return '';
	}

	return sprintf(
		'<li class="menu-item menu-item--cta"><a class="btn" href="%1$s">%2$s</a></li>',
		esc_url( $url ),
		esc_html( $label )
	);
}
