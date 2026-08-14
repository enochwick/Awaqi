<?php
/**
 * Blog index.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<main id="content" class="site-main">
	<?php get_template_part( 'parts/post-list' ); ?>
</main>

<?php get_footer(); ?>
