<?php
/**
 * Fallback template.
 *
 * Required by WordPress. home.php, archive.php, page.php and single.php take
 * precedence — this catches anything they do not.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<main id="content" class="site-main">
	<?php get_template_part( 'parts/post-list' ); ?>
</main>

<?php get_footer(); ?>
