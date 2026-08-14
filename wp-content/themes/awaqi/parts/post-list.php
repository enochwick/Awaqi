<?php
/**
 * Shared post loop for the blog index and archives.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

if ( ! have_posts() ) :
	?>
	<article class="entry">
		<h2 class="entry__title t-section"><?php esc_html_e( 'Nothing here yet', 'awaqi' ); ?></h2>
		<div class="entry__content">
			<p><?php esc_html_e( 'There is no content to show at this address.', 'awaqi' ); ?></p>
		</div>
	</article>
	<?php
	return;
endif;

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'entry' ); ?>>
		<h2 class="entry__title t-section">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p class="entry__meta t-meta">
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</p>

		<div class="entry__content">
			<?php the_excerpt(); ?>
		</div>
	</article>
	<?php
endwhile;

the_posts_pagination( array(
	'class'     => 'pagination',
	'mid_size'  => 1,
	'prev_text' => esc_html__( 'Previous', 'awaqi' ),
	'next_text' => esc_html__( 'Next', 'awaqi' ),
) );
