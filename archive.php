<?php
/**
 * Archive and category template.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<main id="content" class="site-main">
	<header class="entry">
		<h1 class="entry__title t-section"><?php the_archive_title(); ?></h1>
		<?php the_archive_description( '<p class="t-lead">', '</p>' ); ?>
	</header>

	<?php get_template_part( 'parts/post-list' ); ?>
</main>

<?php get_footer(); ?>
