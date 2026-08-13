<?php
/**
 * Awaqi theme setup.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

define( 'AWAQI_VERSION', '1.0.0' );

/**
 * The Spline scene the front page renders when nothing is set in the Customizer.
 */
define( 'AWAQI_DEFAULT_SCENE', 'https://my.spline.design/awaqiaiformobile-petbTiUGXaQcJ56veNakhd4C/' );

/**
 * Theme supports and menu registration.
 */
function awaqi_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
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

/**
 * Front-end assets. Versioned by file mtime so a deploy busts the cache without
 * anyone having to remember to bump a number.
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

	if ( awaqi_is_scene() ) {
		wp_enqueue_script(
			'awaqi-scene',
			$uri . '/assets/js/scene.js',
			array(),
			awaqi_asset_version( $dir . '/assets/js/scene.js' ),
			true
		);
	}

	// model-viewer is a ~1 MB bundle, so it only loads on views that show a model.
	if ( awaqi_has_model() && awaqi_is_scene() ) {
		wp_enqueue_script(
			'awaqi-model-viewer',
			$uri . '/assets/js/vendor/model-viewer.min.js',
			array(),
			'4.3.1',
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

/**
 * model-viewer ships as an ES module, which WordPress has no native flag for.
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @return string
 */
function awaqi_module_script( $tag, $handle ) {
	if ( 'awaqi-model-viewer' === $handle ) {
		$tag = str_replace( '<script ', '<script type="module" ', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'awaqi_module_script', 10, 2 );

/**
 * Whether the current view is the immersive 3D scene.
 *
 * @return bool
 */
function awaqi_is_scene() {
	return is_front_page() && ! is_paged();
}

/**
 * Path to the bundled GLB, relative to the theme.
 */
define( 'AWAQI_MODEL_PATH', '/assets/models/interior.glb' );

/**
 * Whether an optimized model is present in the theme.
 *
 * The GLB is built from raw source that never enters git, so a checkout can
 * legitimately be missing it. Everything model-related keys off this.
 *
 * @return bool
 */
function awaqi_has_model() {
	static $exists = null;

	if ( null === $exists ) {
		$exists = file_exists( get_template_directory() . AWAQI_MODEL_PATH );
	}

	return $exists;
}

/**
 * URL of the bundled model, or an empty string when there is none.
 *
 * @return string
 */
function awaqi_model_url() {
	return awaqi_has_model() ? get_template_directory_uri() . AWAQI_MODEL_PATH : '';
}

/**
 * Lock scrolling on the scene view only.
 *
 * @param array $classes Body classes.
 * @return array
 */
function awaqi_body_class( $classes ) {
	if ( awaqi_is_scene() ) {
		// With a model section below it the page has to scroll; without one the
		// scene stays a locked single screen.
		$classes[] = awaqi_has_model() ? 'awaqi-scene awaqi-scene--scroll' : 'awaqi-scene';
	}

	return $classes;
}
add_filter( 'body_class', 'awaqi_body_class' );

/**
 * Theme options. Everything the client-facing copy needs lives in the
 * Customizer so the site can be edited without touching a deploy.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function awaqi_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'awaqi_scene', array(
		'title'       => __( 'Awaqi — Scene & Hero', 'awaqi' ),
		'priority'    => 25,
		'description' => __( 'Controls the interactive 3D scene and the copy layered over it on the front page.', 'awaqi' ),
	) );

	$fields = array(
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
		'awaqi_model_heading' => array(
			'default'  => __( 'Step inside the space.', 'awaqi' ),
			'label'    => __( 'Model section heading', 'awaqi' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
			'help'     => __( 'Only shown when a GLB model is bundled with the theme.', 'awaqi' ),
		),
		'awaqi_model_text' => array(
			'default'  => __( 'A real-time, fully navigable interior — drag to orbit, scroll to move closer.', 'awaqi' ),
			'label'    => __( 'Model section paragraph', 'awaqi' ),
			'type'     => 'textarea',
			'sanitize' => 'sanitize_textarea_field',
		),
		'awaqi_model_poster' => array(
			'default'  => '',
			'label'    => __( 'Model poster image URL', 'awaqi' ),
			'type'     => 'url',
			'sanitize' => 'esc_url_raw',
			'help'     => __( 'Shown while the model loads. Use one of the 4K renders.', 'awaqi' ),
		),
	);

	foreach ( $fields as $id => $field ) {
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
 * Read a theme option, falling back to its registered default.
 *
 * @param string $key     Option key.
 * @param string $default Fallback value.
 * @return string
 */
function awaqi_option( $key, $default = '' ) {
	$value = get_theme_mod( $key, $default );

	return is_string( $value ) ? $value : $default;
}

/**
 * Renders the site brand — custom logo when one is set, wordmark otherwise.
 */
function awaqi_brand() {
	if ( has_custom_logo() ) {
		$logo = get_custom_logo();
		// Reuse the brand class so spacing matches the wordmark variant.
		echo str_replace( 'custom-logo-link', 'custom-logo-link brand', $logo ); // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}

	$name  = get_bloginfo( 'name' );
	$parts = explode( ' ', $name, 2 );
	?>
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<span class="brand__dot" aria-hidden="true"></span>
		<?php echo esc_html( $parts[0] ); ?>
		<?php if ( isset( $parts[1] ) ) : ?>
			<span class="brand__suffix">&nbsp;<?php echo esc_html( $parts[1] ); ?></span>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * Primary nav, with the CTA button appended. Falls back to a page list when no
 * menu has been assigned yet, so a fresh install still looks intentional.
 */
function awaqi_nav() {
	$cta_label = awaqi_option( 'awaqi_cta_label', __( 'Join waitlist', 'awaqi' ) );
	$cta_url   = awaqi_option( 'awaqi_cta_url', '#waitlist' );
	?>
	<nav class="nav" aria-label="<?php esc_attr_e( 'Primary', 'awaqi' ); ?>">
		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => '',
				'depth'          => 1,
				'items_wrap'     => '<ul>%3$s' . awaqi_cta_item( $cta_label, $cta_url ) . '</ul>',
				'fallback_cb'    => false,
			) );
		} else {
			echo '<ul>' . awaqi_cta_item( $cta_label, $cta_url ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
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
