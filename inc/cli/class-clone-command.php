<?php
/**
 * WP-CLI command: `wp roadmap-starter clone <new-slug>`.
 *
 * Creates a sibling theme directory based on the current one. The default
 * (`--dev`) mode is optimised for fast iteration during skill development:
 * heavy directories (node_modules/, vendor/, vendor_prefixed/) are symlinked
 * back to the source so the clone is runnable in seconds without re-running
 * composer or npm.
 *
 * @package RoadmapStarter\CLI
 */

declare( strict_types = 1 );

namespace RoadmapStarter\CLI;

use WP_CLI;
use WP_CLI\Utils;

final class Clone_Command {

	/**
	 * Excluded paths that get symlinked (dev) or copied (production).
	 *
	 * @var string[]
	 */
	private const HEAVY_DIRS = array(
		'node_modules',
		'vendor',
		'vendor_prefixed',
	);

	/**
	 * Always-excluded paths — never copied or symlinked, regardless of mode.
	 *
	 * @var string[]
	 */
	private const ALWAYS_EXCLUDE = array(
		'.git',
		'tmp',
		'.DS_Store',
	);

	/**
	 * Clone the current theme into a new sibling directory.
	 *
	 * ## OPTIONS
	 *
	 * <new-slug>
	 * : The directory name (and theme slug) for the new theme. Lowercase
	 * letters, numbers, and hyphens only.
	 *
	 * [--name=<name>]
	 * : Human-readable Theme Name to write into the clone's style.css. Defaults
	 * to a humanised version of <new-slug>.
	 *
	 * [--activate]
	 * : Activate the cloned theme immediately on success.
	 *
	 * [--force]
	 * : Overwrite an existing directory at the destination path.
	 *
	 * ## EXAMPLES
	 *
	 *     # Fast dev clone, symlinks node_modules/vendor back to source
	 *     wp roadmap-starter clone test-acme --activate
	 *
	 *     # Clone with a custom display name
	 *     wp roadmap-starter clone client-acme --name="Acme Industries"
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function clone_( array $args, array $assoc_args ): void {
		$new_slug = (string) $args[0];

		if ( ! preg_match( '/^[a-z0-9][a-z0-9-]*$/', $new_slug ) ) {
			WP_CLI::error( sprintf( 'Invalid slug "%s". Use lowercase letters, numbers and hyphens (must start with a letter or number).', $new_slug ) );
		}

		$source_dir = dirname( __DIR__, 2 );
		$source_slug = basename( $source_dir );

		if ( $new_slug === $source_slug ) {
			WP_CLI::error( sprintf( 'New slug "%s" matches the current theme. Pick a different name.', $new_slug ) );
		}

		$dest_dir = dirname( $source_dir ) . '/' . $new_slug;
		$force    = (bool) Utils\get_flag_value( $assoc_args, 'force', false );
		$activate = (bool) Utils\get_flag_value( $assoc_args, 'activate', false );

		if ( is_dir( $dest_dir ) ) {
			if ( ! $force ) {
				WP_CLI::error( sprintf( 'Destination "%s" already exists. Use --force to overwrite.', $dest_dir ) );
			}
			WP_CLI::log( sprintf( 'Removing existing "%s"…', $dest_dir ) );
			self::rrmdir( $dest_dir );
		}

		$pretty_name = isset( $assoc_args['name'] )
			? (string) $assoc_args['name']
			: self::humanise_slug( $new_slug );

		WP_CLI::log( sprintf( 'Cloning %s → %s…', $source_slug, $new_slug ) );

		self::copy_excluding( $source_dir, $dest_dir, array_merge( self::HEAVY_DIRS, self::ALWAYS_EXCLUDE ) );

		// Symlink heavy directories so the clone is immediately runnable
		// without composer/npm.
		foreach ( self::HEAVY_DIRS as $dir ) {
			$source_path = $source_dir . '/' . $dir;
			$dest_path   = $dest_dir . '/' . $dir;
			if ( ! is_dir( $source_path ) ) {
				continue;
			}
			if ( ! @symlink( $source_path, $dest_path ) ) {
				WP_CLI::warning( sprintf(
					'Could not symlink %s → %s. The clone is still functional but JS/PHP autoload may be missing until you run npm install / composer install in the new directory.',
					$dest_path,
					$source_path
				) );
			}
		}

		self::rewrite_style_css( $dest_dir, $new_slug, $pretty_name );

		WP_CLI::success( sprintf( 'Cloned to %s', $dest_dir ) );
		WP_CLI::warning( sprintf(
			'Heavy dirs (node_modules, vendor, vendor_prefixed) are SYMLINKED to the source. To remove this clone safely, run `wp roadmap-starter remove %s` — NOT `wp theme delete`, which follows symlinks and will wipe the source.',
			$new_slug
		) );

		if ( $activate ) {
			WP_CLI::runcommand( 'theme activate ' . escapeshellarg( $new_slug ) );
		} else {
			WP_CLI::log( sprintf( 'Run `wp theme activate %s` to switch.', $new_slug ) );
		}
	}

	/**
	 * Remove a cloned theme safely.
	 *
	 * Unlinks the heavy-directory symlinks first so a recursive delete cannot
	 * follow them back into the source theme. Refuses to remove the currently
	 * active theme or the source theme itself.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the cloned theme to remove.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp roadmap-starter remove test-acme --yes
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function remove( array $args, array $assoc_args ): void {
		$slug = (string) $args[0];

		$source_dir  = dirname( __DIR__, 2 );
		$source_slug = basename( $source_dir );

		if ( $slug === $source_slug ) {
			WP_CLI::error( sprintf( 'Refusing to remove the source theme "%s".', $slug ) );
		}

		$target_dir = dirname( $source_dir ) . '/' . $slug;
		if ( ! is_dir( $target_dir ) ) {
			WP_CLI::error( sprintf( 'No such theme directory: %s', $target_dir ) );
		}

		$current = wp_get_theme();
		if ( $current && $current->get_stylesheet() === $slug ) {
			WP_CLI::error( sprintf( 'Theme "%s" is currently active. Switch away first: `wp theme activate %s`.', $slug, $source_slug ) );
		}

		WP_CLI::confirm( sprintf( 'Delete theme directory %s?', $target_dir ), $assoc_args );

		// Unlink symlinks BEFORE recursive delete so we never descend through
		// them into the source theme.
		foreach ( self::HEAVY_DIRS as $dir ) {
			$path = $target_dir . '/' . $dir;
			if ( is_link( $path ) ) {
				unlink( $path );
			}
		}

		self::rrmdir( $target_dir );

		WP_CLI::success( sprintf( 'Removed %s', $target_dir ) );
	}

	/**
	 * Recursively copy $source to $dest while excluding any top-level
	 * directories named in $exclude.
	 *
	 * @param string   $source  Absolute source path.
	 * @param string   $dest    Absolute destination path.
	 * @param string[] $exclude Top-level directory names to skip.
	 */
	private static function copy_excluding( string $source, string $dest, array $exclude ): void {
		if ( ! is_dir( $dest ) && ! mkdir( $dest, 0755, true ) && ! is_dir( $dest ) ) {
			WP_CLI::error( sprintf( 'Could not create directory: %s', $dest ) );
		}

		$exclude_lookup = array_flip( $exclude );

		$entries = scandir( $source );
		if ( $entries === false ) {
			WP_CLI::error( sprintf( 'Could not read source directory: %s', $source ) );
		}

		foreach ( $entries as $entry ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}
			if ( isset( $exclude_lookup[ $entry ] ) ) {
				continue;
			}

			$from = $source . '/' . $entry;
			$to   = $dest . '/' . $entry;

			if ( is_link( $from ) ) {
				$target = readlink( $from );
				if ( $target !== false ) {
					@symlink( $target, $to );
				}
				continue;
			}

			if ( is_dir( $from ) ) {
				self::copy_excluding( $from, $to, array() );
				continue;
			}

			if ( ! copy( $from, $to ) ) {
				WP_CLI::warning( sprintf( 'Failed to copy %s → %s', $from, $to ) );
			}
		}
	}

	/**
	 * Recursive rmdir that also handles symlinks (without following them).
	 */
	private static function rrmdir( string $path ): void {
		if ( is_link( $path ) ) {
			unlink( $path );
			return;
		}
		if ( ! is_dir( $path ) ) {
			if ( is_file( $path ) ) {
				unlink( $path );
			}
			return;
		}
		foreach ( scandir( $path ) ?: array() as $entry ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}
			self::rrmdir( $path . '/' . $entry );
		}
		rmdir( $path );
	}

	/**
	 * Rewrite style.css header in the clone.
	 *
	 * Only Theme Name and Text Domain are touched. Text Domain stays as the
	 * source's value by default (so dev clones share translation files);
	 * production-clone mode (future) will rewrite it to match the new slug.
	 */
	private static function rewrite_style_css( string $dest_dir, string $new_slug, string $pretty_name ): void {
		$style_file = $dest_dir . '/style.css';
		$css = file_get_contents( $style_file );
		if ( $css === false ) {
			WP_CLI::warning( sprintf( 'Could not read %s — skipping style.css rewrite.', $style_file ) );
			return;
		}

		$css = preg_replace(
			'/^(\s*Theme Name:\s*).+$/mi',
			'${1}' . $pretty_name,
			$css,
			1
		);

		// Update Text Domain too — keeps it aligned with the slug, which is
		// the WP recommendation. Dev clones get a fresh domain; if you want
		// to share .mo files with the source theme, set it back manually.
		$css = preg_replace(
			'/^(\s*Text Domain:\s*).+$/mi',
			'${1}' . $new_slug,
			$css,
			1
		);

		if ( file_put_contents( $style_file, $css ) === false ) {
			WP_CLI::warning( sprintf( 'Could not write %s', $style_file ) );
		}
	}

	/**
	 * Turn "client-acme" → "Client Acme".
	 */
	private static function humanise_slug( string $slug ): string {
		return ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
	}
}
