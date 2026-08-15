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

// The loader belongs to a genuine first visit. A signup return is a second
// page load, and replaying the curtain there makes the site feel restarted.
$returning = awaqi_is_waitlist_return();
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

	</div>
</div>

<?php if ( $scene_url && ! $returning ) : ?>
	<div class="loader" data-loader>
		<?php
		/*
		 * Ported from a React + motion component. The three paths share an
		 * identical command structure (M + 4C + Z), so SVG interpolates the `d`
		 * attribute natively — no runtime, no build step. keySplines reproduce
		 * the original easeInOut across each of the four segments.
		 */
		?>
		<svg class="loader__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
			stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
			role="status" aria-label="<?php esc_attr_e( 'Loading', 'awaqi' ); ?>">
			<path d="M 12 8 C 14.21 8 16 9.79 16 12 C 16 14.21 14.21 16 12 16 C 9.79 16 8 14.21 8 12 C 8 9.79 9.79 8 12 8 Z">
				<animate
					attributeName="d"
					dur="5s"
					repeatCount="indefinite"
					calcMode="spline"
					keyTimes="0;0.25;0.5;0.75;1"
					keySplines="0.42 0 0.58 1;0.42 0 0.58 1;0.42 0 0.58 1;0.42 0 0.58 1"
					values="M 12 8 C 14.21 8 16 9.79 16 12 C 16 14.21 14.21 16 12 16 C 9.79 16 8 14.21 8 12 C 8 9.79 9.79 8 12 8 Z;
					        M 12 12 C 14 8.5 19 8.5 19 12 C 19 15.5 14 15.5 12 12 C 10 8.5 5 8.5 5 12 C 5 15.5 10 15.5 12 12 Z;
					        M 12 16 C 14.21 16 16 14.21 16 12 C 16 9.79 14.21 8 12 8 C 9.79 8 8 9.79 8 12 C 8 14.21 9.79 16 12 16 Z;
					        M 12 12 C 14 8.5 19 8.5 19 12 C 19 15.5 14 15.5 12 12 C 10 8.5 5 8.5 5 12 C 5 15.5 10 15.5 12 12 Z;
					        M 12 8 C 14.21 8 16 9.79 16 12 C 16 14.21 14.21 16 12 16 C 9.79 16 8 14.21 8 12 C 8 9.79 9.79 8 12 8 Z" />
			</path>
		</svg>
		<div class="loader__mark"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
	</div>
<?php endif; ?>
