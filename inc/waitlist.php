<?php
/**
 * Waitlist signups.
 *
 * Stores each address as a private `awaqi_lead` post so signups are visible in
 * wp-admin without a plugin or an external service, and notifies the site admin.
 *
 * @package Awaqi
 */

defined( 'ABSPATH' ) || exit;

const AWAQI_LEAD_CPT = 'awaqi_lead';

/**
 * Registers the signup store.
 */
function awaqi_register_leads() {
	register_post_type( AWAQI_LEAD_CPT, array(
		'labels' => array(
			'name'          => __( 'Waitlist', 'awaqi' ),
			'singular_name' => __( 'Signup', 'awaqi' ),
			'menu_name'     => __( 'Waitlist', 'awaqi' ),
			'all_items'     => __( 'All signups', 'awaqi' ),
			'search_items'  => __( 'Search signups', 'awaqi' ),
			'not_found'     => __( 'No signups yet.', 'awaqi' ),
		),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-email-alt',
		'menu_position'       => 26,
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
		'exclude_from_search' => true,
		'has_archive'         => false,
		'rewrite'             => false,
	) );
}
add_action( 'init', 'awaqi_register_leads' );

/**
 * Shows the signup date instead of the useless default columns.
 *
 * @param array $cols Columns.
 * @return array
 */
function awaqi_lead_columns( $cols ) {
	return array(
		'cb'    => isset( $cols['cb'] ) ? $cols['cb'] : '',
		'title' => __( 'Email address', 'awaqi' ),
		'date'  => __( 'Joined', 'awaqi' ),
	);
}
add_filter( 'manage_' . AWAQI_LEAD_CPT . '_posts_columns', 'awaqi_lead_columns' );

/**
 * Handles the form post.
 *
 * Registered for logged-out and logged-in visitors alike, since the front page
 * is public.
 */
function awaqi_handle_waitlist() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	// Bots fill hidden fields; people do not. Fail silently so they learn nothing.
	if ( ! empty( $_POST['awaqi_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'joined', '1', $back ) . '#waitlist' );
		exit;
	}

	if ( ! isset( $_POST['awaqi_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['awaqi_nonce'] ) ), 'awaqi_waitlist' ) ) {
		awaqi_waitlist_back( $back, 'expired' );
	}

	$email = isset( $_POST['awaqi_email'] ) ? sanitize_email( wp_unslash( $_POST['awaqi_email'] ) ) : '';

	if ( '' === $email || ! is_email( $email ) ) {
		awaqi_waitlist_back( $back, 'invalid' );
	}

	// Quietly treat a repeat signup as success — no need to leak who is on the list.
	// get_page_by_title() is deprecated as of WP 6.2, so query directly.
	$existing = get_posts( array(
		'post_type'        => AWAQI_LEAD_CPT,
		'post_status'      => 'private',
		'title'            => $email,
		'posts_per_page'   => 1,
		'fields'           => 'ids',
		'no_found_rows'    => true,
		'suppress_filters' => false,
	) );

	if ( ! $existing ) {
		$id = wp_insert_post( array(
			'post_type'   => AWAQI_LEAD_CPT,
			'post_title'  => $email,
			'post_status' => 'private',
		), true );

		if ( is_wp_error( $id ) ) {
			awaqi_waitlist_back( $back, 'failed' );
		}

		awaqi_notify_admin( $email );

		/**
		 * Fires after a new address is stored.
		 *
		 * The place to push signups to an external list (Mailchimp, ConvertKit,
		 * Resend) without touching the template or the handler.
		 *
		 * @param string $email Signup address.
		 * @param int    $id    Stored post ID.
		 */
		do_action( 'awaqi_waitlist_signup', $email, $id );
	}

	wp_safe_redirect( add_query_arg( 'joined', '1', $back ) . '#waitlist' );
	exit;
}
add_action( 'admin_post_awaqi_waitlist', 'awaqi_handle_waitlist' );
add_action( 'admin_post_nopriv_awaqi_waitlist', 'awaqi_handle_waitlist' );

/**
 * Redirects back with an error code.
 *
 * @param string $back  Return URL.
 * @param string $error Error slug.
 */
function awaqi_waitlist_back( $back, $error ) {
	wp_safe_redirect( add_query_arg( array( 'joined' => '0', 'err' => $error ), $back ) . '#waitlist' );
	exit;
}

/**
 * Emails the site admin. Failure is non-fatal — the signup is already stored.
 *
 * @param string $email Signup address.
 */
function awaqi_notify_admin( $email ) {
	if ( ! apply_filters( 'awaqi_notify_admin', true, $email ) ) {
		return;
	}

	wp_mail(
		get_option( 'admin_email' ),
		sprintf( /* translators: %s: site name */ __( '[%s] New waitlist signup', 'awaqi' ), get_bloginfo( 'name' ) ),
		sprintf( /* translators: %s: email address */ __( "%s joined the waitlist.\n\nSee all signups: ", 'awaqi' ), $email )
			. admin_url( 'edit.php?post_type=' . AWAQI_LEAD_CPT )
	);
}

/**
 * Whether this request is the redirect back from a form submission.
 *
 * The handler POSTs to admin-post.php and redirects, so the front page loads a
 * second time. The intro loader belongs to a first visit only — replaying it
 * after someone signs up makes the site feel like it restarted.
 *
 * @return bool
 */
function awaqi_is_waitlist_return() {
	return isset( $_GET['joined'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * The message to show after a redirect back from the handler.
 *
 * @return array{type:string,text:string}|null
 */
function awaqi_waitlist_notice() {
	if ( ! isset( $_GET['joined'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return null;
	}

	if ( '1' === $_GET['joined'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return array(
			'type' => 'ok',
			'text' => awaqi_field( 'awaqi_waitlist_success' ),
		);
	}

	$err = isset( $_GET['err'] ) ? sanitize_key( wp_unslash( $_GET['err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$messages = array(
		'invalid' => __( 'That email address does not look right. Check it and try again.', 'awaqi' ),
		'expired' => __( 'That form timed out. Try again.', 'awaqi' ),
		'failed'  => __( 'Something went wrong saving your address. Try again in a moment.', 'awaqi' ),
	);

	return array(
		'type' => 'error',
		'text' => isset( $messages[ $err ] ) ? $messages[ $err ] : $messages['failed'],
	);
}
