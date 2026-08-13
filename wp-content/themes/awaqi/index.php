<?php
/**
 * Fallback template — post archives and the blog index.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="content" class="site-main">
	<?php if ( have_posts() ) : ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'entry' ); ?>>
				<h2 class="entry__title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>

				<p class="entry__meta">
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
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
		?>

	<?php else : ?>

		<article class="entry">
			<h1 class="entry__title"><?php esc_html_e( 'Nothing here yet', 'awaqi' ); ?></h1>
			<div class="entry__content">
				<p><?php esc_html_e( 'There is no content to show at this address.', 'awaqi' ); ?></p>
			</div>
		</article>

	<?php endif; ?>
</main>

<?php
get_footer();
