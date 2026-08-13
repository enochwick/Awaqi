<?php
/**
 * 404.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main">
	<article class="entry">
		<h1 class="entry__title"><?php esc_html_e( 'Page not found', 'awaqi' ); ?></h1>
		<div class="entry__content">
			<p><?php esc_html_e( 'That address does not lead anywhere. It may have moved.', 'awaqi' ); ?></p>
			<p><a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'awaqi' ); ?></a></p>
		</div>
	</article>
</main>

<?php
get_footer();
