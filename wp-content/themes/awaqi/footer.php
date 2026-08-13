<?php
/**
 * Closing markup.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( ! awaqi_is_scene() ) : ?>
	<footer class="site-footer site-footer--flow">
		<?php
		printf(
			/* translators: 1: year, 2: site name */
			esc_html__( '© %1$s %2$s', 'awaqi' ),
			esc_html( gmdate( 'Y' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
		?>
	</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
