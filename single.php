<?php
/**
 * Single post template.
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

			<p class="entry__meta t-meta">
				<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
					<?php echo esc_html( get_the_date() ); ?>
				</time>
			</p>

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

		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}

	endwhile;
	?>
</main>

<?php get_footer(); ?>
