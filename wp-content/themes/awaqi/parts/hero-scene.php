<?php
/**
 * The immersive Spline scene with its overlay chrome.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

$scene_url = awaqi_option( 'awaqi_scene_url', AWAQI_DEFAULT_SCENE );
$heading   = awaqi_option( 'awaqi_hero_heading' );
$text      = awaqi_option( 'awaqi_hero_text' );
$hint      = awaqi_option( 'awaqi_hint_text' );
?>

<?php if ( $scene_url ) : ?>
	<div class="scene" data-scene data-timeout="8000">
		<iframe
			src="<?php echo esc_url( $scene_url ); ?>"
			title="<?php echo esc_attr( sprintf( /* translators: %s: site name */ __( '%s interactive 3D scene', 'awaqi' ), get_bloginfo( 'name' ) ) ); ?>"
			loading="eager"
			allow="autoplay; fullscreen; xr-spatial-tracking"
			referrerpolicy="no-referrer-when-downgrade"></iframe>
	</div>
	<div class="scene-veil" aria-hidden="true"></div>
<?php endif; ?>

<div class="overlay">
	<header class="site-header">
		<?php awaqi_brand(); ?>
		<?php awaqi_nav(); ?>
	</header>

	<div class="hero-row">
		<div class="hero">
			<?php if ( $heading ) : ?>
				<h1 class="hero__title"><?php echo esc_html( $heading ); ?></h1>
			<?php endif; ?>

			<?php if ( $text ) : ?>
				<p class="hero__text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $hint ) : ?>
			<p class="hint">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<path d="M12 2v4M12 18v4M2 12h4M18 12h4M5 5l3 3M16 16l3 3M19 5l-3 3M8 16l-3 3"/>
				</svg>
				<?php echo esc_html( $hint ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>

<?php if ( $scene_url ) : ?>
	<div class="loader" data-loader>
		<div class="loader__mark"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
		<div class="loader__bar"></div>
	</div>
<?php endif; ?>
