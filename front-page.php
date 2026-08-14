<?php
/**
 * Front page — the interactive scene.
 *
 * If the front page is set to a static page with content, that content renders
 * below the scene as a normal scrolling document.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content">
	<?php
	get_template_part( 'parts/hero-scene' );
	get_template_part( 'parts/waitlist' );
	?>
</main>

<?php
get_footer();
