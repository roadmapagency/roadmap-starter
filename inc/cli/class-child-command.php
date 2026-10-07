<?php
/**
 * WP-CLI commands for roadmap-starter child themes:
 *   wp roadmap-starter scaffold-child <slug> [--name=<name>] [--namespace=<ns>] [--link-node-modules] [--activate]
 *   wp roadmap-starter child-audit [<slug>]
 *
 * New sites are child themes of this parent (scaffold/child/ is the template), not copies of it.
 *
 * @package RoadmapStarter\CLI
 */

declare( strict_types = 1 );

namespace RoadmapStarter\CLI;

use WP_CLI;
use WP_CLI\Utils;

final class Child_Command {

	/**
	 * Create a child theme of roadmap-starter for a new site.
	 *
	 * Copies scaffold/child (style.css with `Template: roadmap-starter`, functions.php, token and site
	 * SCSS, the build config that reuses the parent's webpack factory, and the Hero / FAQs /
	 * ImageAndText starter blocks re-namespaced for the site) and writes package.json from the
	 * parent's, so the child builds with the same toolchain.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Directory name and theme slug. Lowercase letters, numbers and hyphens.
	 *
	 * [--name=<name>]
	 * : Theme Name. Defaults to the humanised slug.
	 *
	 * [--namespace=<namespace>]
	 * : PHP namespace for the site's blocks. Defaults to StudlyCaps(slug)\Blocks.
	 *
	 * [--link-node-modules]
	 * : Symlink node_modules to the parent's instead of requiring `npm install` (local dev only).
	 *
	 * [--activate]
	 * : Activate the child theme afterwards.
	 *
	 * ## EXAMPLES
	 *
	 *     wp roadmap-starter scaffold-child client-acme --name="Acme Industries"
	 *
	 * @subcommand scaffold-child
	 * @when after_wp_load
	 *
	 * @param array<int, string>         $args       Positional arguments.
	 * @param array<string, string|bool> $assoc_args Associative arguments.
	 */
	public function scaffold_child( array $args, array $assoc_args ): void {
		$slug = (string) $args[0];
		if ( ! preg_match( '/^[a-z0-9][a-z0-9-]*$/', $slug ) || 'roadmap-starter' === $slug ) {
			WP_CLI::error( 'Slug must be lowercase letters, numbers and hyphens, and not roadmap-starter.' );
		}

		$parent = get_template_directory();
		if ( basename( $parent ) !== 'roadmap-starter' ) {
			$parent = get_theme_root() . '/roadmap-starter';
		}
		$dest = get_theme_root() . '/' . $slug;
		if ( file_exists( $dest ) ) {
			WP_CLI::error( "{$dest} already exists." );
		}

		$studly    = str_replace( ' ', '', ucwords( str_replace( '-', ' ', $slug ) ) );
		$name      = (string) Utils\get_flag_value( $assoc_args, 'name', ucwords( str_replace( '-', ' ', $slug ) ) );
		$namespace = trim( (string) Utils\get_flag_value( $assoc_args, 'namespace', $studly . '\\Blocks' ), '\\' );
		if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $namespace ) ) {
			WP_CLI::error( "Invalid namespace: {$namespace}" );
		}

		$vars = array(
			'{{SLUG}}'          => $slug,
			'{{NAME}}'          => $name,
			'{{NAMESPACE}}'     => $namespace,
			'{{NAMESPACE_ESC}}' => str_replace( '\\', '\\\\', $namespace ),
			'{{FN_PREFIX}}'     => str_replace( '-', '_', $slug ),
		);

		$source = $parent . '/scaffold/child';
		$files  = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			$rel    = substr( $file->getPathname(), strlen( $source ) + 1 );
			$target = $dest . '/' . $rel;
			wp_mkdir_p( dirname( $target ) );
			$content = (string) file_get_contents( $file->getPathname() );
			if ( preg_match( '/\.(php|css|scss|js|json)$/', $rel ) ) {
				$content = strtr( $content, $vars );
				// Starter blocks move to the site's namespace (names/ACF keys come from the class name).
				$content = preg_replace( '/^<\?php namespace RoadmapStarter\\\\Blocks\\\\/m', '<?php namespace ' . $namespace . '\\', $content );
			}
			file_put_contents( $target, $content );
		}

		$package                    = json_decode( (string) file_get_contents( $parent . '/package.json' ), true );
		$package['name']            = $slug;
		$package['version']         = '0.1.0';
		$package['description']     = $name . ' — child theme of roadmap-starter';
		$package['scripts']['package'] = str_replace( '-x "$THEME/tmp/*"', '-x "$THEME/tmp/*" -x "$THEME/src/*"', (string) ( $package['scripts']['package'] ?? '' ) );
		file_put_contents( $dest . '/package.json', wp_json_encode( $package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );

		if ( Utils\get_flag_value( $assoc_args, 'link-node-modules', false ) && is_dir( $parent . '/node_modules' ) ) {
			symlink( $parent . '/node_modules', $dest . '/node_modules' );
		}

		WP_CLI::success( "Created child theme {$slug} ({$name}), blocks in {$namespace}." );
		WP_CLI::log( "Next: cd wp-content/themes/{$slug} && " . ( is_link( $dest . '/node_modules' ) ? '' : 'npm install && ' ) . 'npm run build' );

		if ( Utils\get_flag_value( $assoc_args, 'activate', false ) ) {
			WP_CLI::runcommand( 'theme activate ' . $slug );
		}
	}

	/**
	 * Check a child theme for problems that break the parent/child split.
	 *
	 * Fails (exit 1) on: missing `Template: roadmap-starter`; functions the child redeclares from the
	 * parent (fatal at runtime); get_template_directory() in child code (points at the parent); the
	 * PHP block prefix disagreeing with $block-prefix in _tokens.scss. Warns about files identical to
	 * the parent's (delete them, the parent provides them) and a build made against another parent
	 * version (rebuild).
	 *
	 * ## OPTIONS
	 *
	 * [<slug>]
	 * : Child theme directory. Defaults to the active theme.
	 *
	 * @subcommand child-audit
	 * @when after_wp_load
	 *
	 * @param array<int, string> $args Positional arguments.
	 */
	public function child_audit( array $args ): void {
		$slug   = $args[0] ?? get_stylesheet();
		$dir    = get_theme_root() . '/' . $slug;
		$parent = get_theme_root() . '/roadmap-starter';
		$errors = 0;

		$theme = wp_get_theme( $slug );
		if ( 'roadmap-starter' !== $theme->get_template() || $slug === 'roadmap-starter' ) {
			WP_CLI::warning( "{$slug}: style.css has no `Template: roadmap-starter`." );
			++$errors;
		}

		$child_php  = self::php_files( $dir );
		$parent_php = self::php_files( $parent );

		$parent_fns = array();
		foreach ( $parent_php as $file ) {
			foreach ( self::functions( $file ) as $fn ) {
				$parent_fns[ $fn ] = substr( $file, strlen( $parent ) + 1 );
			}
		}
		foreach ( $child_php as $file ) {
			$rel  = substr( $file, strlen( $dir ) + 1 );
			$code = (string) file_get_contents( $file );
			foreach ( self::functions( $file ) as $fn ) {
				// A definition behind `if ( ! function_exists( 'fn' ) )` can't redeclare.
				if ( isset( $parent_fns[ $fn ] ) && ! preg_match( "/function_exists\\(\\s*['\"]" . preg_quote( $fn, '/' ) . "['\"]\\s*\\)/", $code ) ) {
					WP_CLI::warning( "{$rel}: redeclares {$fn}() from the parent's {$parent_fns[ $fn ]} — fatal error." );
					++$errors;
				}
			}
			if ( preg_match( '/get_template_directory(_uri)?\s*\(/', (string) file_get_contents( $file ) ) ) {
				WP_CLI::warning( "{$rel}: uses get_template_directory() — in a child that is the parent; use get_stylesheet_directory() or __DIR__." );
				++$errors;
			}
		}

		$identical = array();
		foreach ( self::files( $dir ) as $rel ) {
			if ( is_file( $parent . '/' . $rel ) && filesize( $dir . '/' . $rel ) === filesize( $parent . '/' . $rel )
				&& sha1_file( $dir . '/' . $rel ) === sha1_file( $parent . '/' . $rel ) ) {
				$identical[] = $rel;
			}
		}
		$identical = array_values( array_filter( $identical, static fn( $rel ) => ! preg_match( '#(^|/)\.DS_Store$|^(\.babelrc|\.nvmrc|\.gitignore|package(-lock)?\.json|LICENSE|CLAUDE\.md|screenshot\.png|src/images/|src/(admin|style-editor|theme)\.js$)#', $rel ) ) );
		if ( $identical ) {
			WP_CLI::warning( count( $identical ) . " file(s) identical to the parent's — delete them so parent updates reach this site:\n  " . implode( "\n  ", array_slice( $identical, 0, 30 ) ) );
		}

		$tokens = $dir . '/src/sass/_tokens.scss';
		if ( is_readable( $tokens ) && preg_match( '/^\$block-prefix:\s*[\'"]([^\'"]+)[\'"]/m', (string) file_get_contents( $tokens ), $m ) && $slug === get_stylesheet() ) {
			$php = \RoadmapStarter\Identity::block_prefix();
			if ( $m[1] !== $php ) {
				WP_CLI::warning( "\$block-prefix '{$m[1]}' in _tokens.scss differs from the PHP block prefix '{$php}' — blocks render unstyled." );
				++$errors;
			}
		}

		$meta = $dir . '/public/build-meta.json';
		if ( is_readable( $meta ) ) {
			$built = (string) ( json_decode( (string) file_get_contents( $meta ), true )['parentVersion'] ?? '' );
			$now   = (string) wp_get_theme( 'roadmap-starter' )->get( 'Version' );
			if ( $built !== $now ) {
				WP_CLI::warning( "Built against roadmap-starter {$built}, installed {$now} — run npm run build." );
			}
		} else {
			WP_CLI::warning( 'No public/build-meta.json — run npm run build.' );
		}

		if ( $errors ) {
			WP_CLI::error( "{$errors} problem(s) in {$slug}." );
		}
		WP_CLI::success( "{$slug} looks like a healthy roadmap-starter child theme." );
	}

	/** @return string[] Absolute paths of PHP files outside build/dependency dirs. */
	private static function php_files( string $dir ): array {
		return array_map( static fn( $rel ) => $dir . '/' . $rel, array_filter( self::files( $dir ), static fn( $rel ) => str_ends_with( $rel, '.php' ) ) );
	}

	/** @return string[] Relative paths, skipping VCS, build output and dependencies. */
	private static function files( string $dir ): array {
		$out = array();
		$it  = new \RecursiveIteratorIterator(
			new \RecursiveCallbackFilterIterator(
				new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
				static fn( $f ) => ! in_array( $f->getFilename(), array( '.git', 'node_modules', 'vendor', 'vendor_prefixed', 'public', 'tmp', 'scaffold', 'bin' ), true )
			)
		);
		foreach ( $it as $file ) {
			$out[] = substr( $file->getPathname(), strlen( $dir ) + 1 );
		}
		sort( $out );
		return $out;
	}

	/** @return string[] Global function names declared in a PHP file (not methods or closures). */
	private static function functions( string $file ): array {
		$tokens = token_get_all( (string) file_get_contents( $file ) );
		$names  = array();
		$depth  = 0;
		$ns     = false;
		foreach ( $tokens as $i => $t ) {
			if ( '{' === $t ) {
				++$depth;
			} elseif ( '}' === $t ) {
				--$depth;
			} elseif ( is_array( $t ) && in_array( $t[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) {
				++$depth;
			} elseif ( is_array( $t ) && T_NAMESPACE === $t[0] ) {
				$ns = true;
			} elseif ( is_array( $t ) && T_FUNCTION === $t[0] && ! $ns ) {
				for ( $j = $i + 1; isset( $tokens[ $j ] ); $j++ ) {
					if ( is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0] ) {
						// Top-level, or inside an if ( ! function_exists() ) guard (depth 1 under `if`).
						if ( $depth <= 1 ) {
							$names[] = $tokens[ $j ][1];
						}
						break;
					}
					if ( '(' === $tokens[ $j ] ) {
						break; // closure
					}
				}
			}
		}
		return $names;
	}
}
