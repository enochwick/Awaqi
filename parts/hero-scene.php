<?php
/**
 * The immersive Spline scene with its overlay chrome.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

$scene_url = awaqi_field( 'awaqi_scene_url' );
$heading   = awaqi_field( 'awaqi_hero_heading' );
$text      = awaqi_field( 'awaqi_hero_text' );
$hint      = awaqi_field( 'awaqi_hint_text' );
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
				<h1 class="hero__title t-hero">
					<?php
					/*
					 * A newline in the heading field becomes a line break that only
					 * applies on wider screens. Escaped first, so the only markup
					 * in the output is the break this adds.
					 *
					 * The trailing space matters: the break is display:none on small
					 * screens, and without it the two lines would run together as
					 * "Intelligence thatmoves". A space directly after a <br> is
					 * collapsed at the start of the new line, so desktop is unaffected.
					 */
					echo preg_replace( '/\R/', '<br class="brk"> ', esc_html( $heading ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</h1>
			<?php endif; ?>

			<?php if ( $text ) : ?>
				<p class="hero__text t-lead"><?php echo esc_html( $text ); ?></p>
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
