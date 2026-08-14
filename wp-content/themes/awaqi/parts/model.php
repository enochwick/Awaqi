<?php
/**
 * The GLB model showcase, rendered below the Spline scene on the front page.
 *
 * Only output when a model file is actually present in the theme, so a fresh
 * checkout without the (git-ignored) GLB degrades to the scene alone.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

$model_url = awaqi_model_url();

if ( ! $model_url ) {
	return;
}

$heading = awaqi_field( 'awaqi_model_heading' );
$text    = awaqi_field( 'awaqi_model_text' );

// Falls back to the render bundled with the theme.
$poster = awaqi_field( 'awaqi_model_poster' );
if ( ! $poster ) {
	$poster = get_template_directory_uri() . '/assets/images/interior-poster.jpg';
}
?>

<section class="model" id="model">
	<div class="model__intro">
		<?php if ( $heading ) : ?>
			<h2 class="model__title t-section"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $text ) : ?>
			<p class="model__text t-lead"><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>
	</div>

	<div class="model__stage">
		<model-viewer
			src="<?php echo esc_url( $model_url ); ?>"
			alt="<?php echo esc_attr( $heading ? $heading : __( 'Interactive 3D model', 'awaqi' ) ); ?>"
			poster="<?php echo esc_url( $poster ); ?>"
			camera-controls
			touch-action="pan-y"
			auto-rotate
			auto-rotate-delay="1200"
			rotation-per-second="12deg"
			interaction-prompt="none"
			shadow-intensity="0.6"
			exposure="1.1"
			environment-image="neutral"
			loading="lazy"
			reveal="auto">
			<div class="model__loading" slot="progress-bar">
				<div class="loader__bar"></div>
			</div>
		</model-viewer>

		<p class="hint model__hint">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
				<path d="M12 2v4M12 18v4M2 12h4M18 12h4M5 5l3 3M16 16l3 3M19 5l-3 3M8 16l-3 3"/>
			</svg>
			<?php esc_html_e( 'Drag to orbit · scroll to zoom', 'awaqi' ); ?>
		</p>
	</div>
</section>
