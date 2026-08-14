<?php
/**
 * Waitlist signup, rendered below the scene on the front page.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

$heading = awaqi_field( 'awaqi_waitlist_heading' );
$text    = awaqi_field( 'awaqi_waitlist_text' );
$button  = awaqi_field( 'awaqi_waitlist_button' );
// Success is confirmed up in the hero; only errors belong beside the field.
$notice  = awaqi_waitlist_notice();
if ( $notice && 'ok' === $notice['type'] ) {
	$notice = null;
}
?>

<section class="waitlist" id="waitlist">
	<div class="waitlist__in" data-reveal>
		<?php if ( $heading ) : ?>
			<h2 class="waitlist__title t-section"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $text ) : ?>
			<p class="waitlist__text t-lead"><?php echo esc_html( $text ); ?></p>
		<?php endif; ?>

		<form class="form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="awaqi_waitlist">
			<?php wp_nonce_field( 'awaqi_waitlist', 'awaqi_nonce' ); ?>

			<?php // Honeypot: hidden from people, irresistible to bots. ?>
			<div class="form__hp" aria-hidden="true">
				<label for="awaqi-website">Website</label>
				<input type="text" id="awaqi-website" name="awaqi_website" tabindex="-1" autocomplete="off">
			</div>

			<div class="form__row">
				<label class="screen-reader-text" for="awaqi-email"><?php esc_html_e( 'Email address', 'awaqi' ); ?></label>
				<input
					class="form__input"
					type="email"
					id="awaqi-email"
					name="awaqi_email"
					required
					autocomplete="email"
					spellcheck="false"
					placeholder="<?php esc_attr_e( 'you@example.com', 'awaqi' ); ?>">
				<button class="btn form__btn" type="submit"><?php echo esc_html( $button ? $button : __( 'Join waitlist', 'awaqi' ) ); ?></button>
			</div>

			<?php if ( $notice ) : ?>
				<p class="form__note form__note--<?php echo esc_attr( $notice['type'] ); ?>" role="status">
					<?php echo esc_html( $notice['text'] ); ?>
				</p>
			<?php endif; ?>

			<p class="form__fine"><?php esc_html_e( 'One email at launch. Zero spam. Zero sharing.', 'awaqi' ); ?></p>
		</form>
	</div>
</section>
