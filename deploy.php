<?php
/**
 * Deployer recipe — Awaqi WordPress theme.
 *
 * This repo holds the theme only, never WordPress core, uploads, or the
 * database. Deployer builds atomic releases outside the web root and points a
 * symlink at wp-content/themes/awaqi, so a bad deploy is one `rollback` away
 * and nothing WordPress owns is ever overwritten.
 *
 * Usage:
 *   vendor/bin/dep deploy production
 *   vendor/bin/dep rollback production
 *   vendor/bin/dep wp:info production
 */

namespace Deployer;

require 'recipe/common.php';

/* -----------------------------------------------------------------------------
 * Project
 * -------------------------------------------------------------------------- */

set( 'application', 'awaqi' );
set( 'theme_slug', 'awaqi' );

// Change this to your repository once it has a remote.
set( 'repository', 'git@github.com:USERNAME/awaqi.git' );

set( 'keep_releases', 5 );
set( 'git_tty', false );
set( 'ssh_multiplexing', true );

// Only the theme directory is copied out of the repo into the release.
set( 'update_code_strategy', 'archive' );
set( 'sub_directory', 'wp-content/themes/{{theme_slug}}' );

// No build step: the theme ships plain CSS and JS on purpose.
set( 'shared_files', [] );
set( 'shared_dirs', [] );
set( 'writable_dirs', [] );

// Files that have no business on a production server.
set( 'clear_paths', [
	'docs',
	'.editorconfig',
	'.gitattributes',
] );

/* -----------------------------------------------------------------------------
 * Hosts
 * -------------------------------------------------------------------------- */

host( 'production' )
	->set( 'hostname', 'awaqi.ai' )
	->set( 'remote_user', 'deploy' )
	->set( 'port', 22 )
	// Where WordPress itself lives on the server (the folder holding wp-config.php).
	->set( 'wp_path', '/var/www/awaqi/public' )
	// Where Deployer keeps releases/, current, and .dep. Keep it out of the web root.
	->set( 'deploy_path', '/var/www/awaqi/deploy/theme' )
	->set( 'branch', 'main' );

host( 'staging' )
	->set( 'hostname', 'staging.awaqi.ai' )
	->set( 'remote_user', 'deploy' )
	->set( 'port', 22 )
	->set( 'wp_path', '/var/www/awaqi-staging/public' )
	->set( 'deploy_path', '/var/www/awaqi-staging/deploy/theme' )
	->set( 'branch', 'main' );

/* -----------------------------------------------------------------------------
 * Derived paths
 * -------------------------------------------------------------------------- */

set( 'themes_path', function () {
	return '{{wp_path}}/wp-content/themes';
} );

set( 'theme_link', function () {
	return '{{themes_path}}/{{theme_slug}}';
} );

/* -----------------------------------------------------------------------------
 * Tasks
 * -------------------------------------------------------------------------- */

/**
 * Points wp-content/themes/awaqi at the current release.
 *
 * WordPress builds theme paths from WP_CONTENT_DIR rather than realpath(), so a
 * symlinked theme directory resolves its URLs correctly. The task refuses to
 * touch a real directory, which stops a first deploy from clobbering a theme
 * that was uploaded by hand.
 */
task( 'wp:link_theme', function () {
	$link = parse( '{{theme_link}}' );

	if ( test( "[ -e $link ] && [ ! -L $link ]" ) ) {
		throw new \RuntimeException(
			"$link exists as a real directory.\n" .
			"Move it aside on the server before deploying:\n" .
			"  mv $link {$link}-manual-backup"
		);
	}

	run( 'mkdir -p {{themes_path}}' );
	run( 'ln -sfn {{deploy_path}}/current {{theme_link}}' );
} )->desc( 'Link the release into wp-content/themes' );

/**
 * Flushes WordPress caches when WP-CLI is available. Silent no-op otherwise.
 */
task( 'wp:flush', function () {
	if ( ! test( 'command -v wp >/dev/null 2>&1' ) ) {
		writeln( '<comment>WP-CLI not found on host, skipping cache flush.</comment>' );
		return;
	}

	run( 'cd {{wp_path}} && wp cache flush --quiet || true' );
	run( 'cd {{wp_path}} && wp rewrite flush --quiet || true' );
} )->desc( 'Flush WordPress caches (needs WP-CLI)' );

/**
 * Prints what is actually live — useful when a deploy "did nothing".
 */
task( 'wp:info', function () {
	writeln( 'Release:  ' . run( 'readlink {{deploy_path}}/current || echo "none"' ) );
	writeln( 'Symlink:  ' . run( 'readlink {{theme_link}} || echo "not linked"' ) );
	writeln( 'Releases: ' . run( 'ls -1 {{deploy_path}}/releases 2>/dev/null | tr "\n" " " || echo "none"' ) );
} )->desc( 'Show the live release and theme symlink' );

/**
 * Basic sanity check so a syntax error never reaches production.
 */
task( 'deploy:verify', function () {
	if ( ! test( 'command -v php >/dev/null 2>&1' ) ) {
		return;
	}

	run( 'cd {{release_path}} && find . -name "*.php" -print0 | xargs -0 -n1 php -l > /dev/null' );
} )->desc( 'Lint the deployed PHP' );

/* -----------------------------------------------------------------------------
 * Flow
 * -------------------------------------------------------------------------- */

desc( 'Deploy the Awaqi theme' );
task( 'deploy', [
	'deploy:prepare',
	'deploy:verify',
	'deploy:publish',
	'wp:link_theme',
	'wp:flush',
] );

// Never leave a lock behind on a failed run.
after( 'deploy:failed', 'deploy:unlock' );

// Re-point the symlink after a rollback too, so `current` and the theme agree.
after( 'rollback', 'wp:link_theme' );
after( 'rollback', 'wp:flush' );
