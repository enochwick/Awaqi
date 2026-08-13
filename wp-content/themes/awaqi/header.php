<?php
/**
 * Document head and opening markup.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'awaqi' ); ?></a>

<?php if ( ! awaqi_is_scene() ) : ?>
	<header class="site-header site-header--flow">
		<?php awaqi_brand(); ?>
		<?php awaqi_nav(); ?>
	</header>
<?php endif; ?>
