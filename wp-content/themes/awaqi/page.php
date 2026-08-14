<?php
/**
 * Default page template.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<main id="content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'entry' ); ?>>
			<h1 class="entry__title t-section"><?php the_title(); ?></h1>

			<div class="entry__content">
				<?php
				the_content();

				wp_link_pages( array(
					'before' => '<p class="pagination">',
					'after'  => '</p>',
				) );
				?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>

<?php get_footer(); ?>
