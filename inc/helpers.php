<?php
/**
 * Helper functions for the theme
 *
 * @package roadmap-starter
 */

/**
 * Gets the path to the theme's public folder
 *
 * @param string $relative_path Beginning slash is optional.
 *
 * @return string
 */
function roadmap_starter_public_dir( $relative_path = '' ) {
	$relative_path = trim( $relative_path, '/' );

	return get_stylesheet_directory() . '/public/' . $relative_path;
}

/**
 * Cache-busting version for a built asset in the active theme's public folder: its mtime, so every
 * build invalidates browser caches. Falls back to the theme version when the file is missing.
 *
 * @param string $relative_path Path relative to public/.
 *
 * @return string
 */
function roadmap_starter_asset_version( $relative_path ) {
	$file = roadmap_starter_public_dir( $relative_path );

	return file_exists( $file ) ? (string) filemtime( $file ) : (string) wp_get_theme()->get( 'Version' );
}

/**
 * Gets the uri to the theme's public folder
 *
 * @param string $relative_path Beginning slash is optional.
 *
 * @return string
 */
function roadmap_starter_public_uri( $relative_path = '' ) {
	$relative_path = trim( $relative_path, '/' );

	return get_stylesheet_directory_uri() . '/public/' . $relative_path;
}

/**
 * Get the permalink by post slug.
 *
 * @param string $slug The post's slug.
 *
 * @return false|string
 */
function roadmap_starter_get_permalink_by_slug( $slug ) {
	return get_permalink( get_page_by_path( $slug ) );
}

/**
 * Get the srcset value for an image
 *
 * @param string $at_1x_filename Should be relative to public/.
 *
 * @return string returns the value of a srcset for a given image.
 */
function roadmap_starter_asset_srcset( $at_1x_filename ) {
	$srcs   = array();
	$srcs[] = array(
		'size' => '1x',
		'url'  => roadmap_starter_public_uri( $at_1x_filename ),
	);

	$parts = pathinfo( $at_1x_filename );
	$file  = $parts['filename'] . '@*';

	$files = glob( roadmap_starter_public_dir() . '**/' . $file );
	$files = array_merge(
		$files,
		glob( roadmap_starter_public_dir() . '**/' . $file )
	); // Ideally I'd like to do this in 1 glob call but not sure yet how to get it recursive.

	$files = array_unique( $files );

	foreach ( $files as $file ) {
		$file = str_replace( roadmap_starter_public_dir(), '', $file );
		preg_match( '/@([\d]{1,}x)\./', $file, $size );

		if ( empty( $size[1] ) ) {
			continue;
		}

		$srcs[] = array(
			'size' => $size[1],
			'url'  => roadmap_starter_public_uri( $file ),
		);
	}

	$srcset = '';
	foreach ( $srcs as $src ) {
		$srcset .= $src['url'] . ' ' . $src['size'] . ', ';
	}

	return trim( $srcset, ', ' );
}

/**
 * Get the img tag for an image
 *
 * @param string $at_1x_filename Relative image to public/images.
 * @param string $alt Image alt text.
 * @param string $classes Image classes.
 * @param string $width Image width (units allowed).
 * @param string $height Image height (units allowed).
 *
 * @return string Returns the img tag for a given image
 */
function roadmap_starter_get_img( $at_1x_filename, $alt, $classes = '', $width = '', $height = '' ) {
	$at_1x_filename = trim( $at_1x_filename, '/' );
	$src            = roadmap_starter_public_uri( 'images/' . $at_1x_filename );

	$srcset = '';
	if ( 'svg' !== pathinfo( $at_1x_filename )['extension'] ) {
		$srcset = 'srcset="' . roadmap_starter_asset_srcset( 'images/' . $at_1x_filename ) . '"';
	}

	$dimensions  = $width ? 'width="' . $width . '"' : '';
	$dimensions .= $height ? ' height="' . $height . '"' : '';

	$alt = addslashes( $alt );

	return "<img class=\"$classes\" src=\"$src\" $srcset $dimensions loading='lazy' alt=\"$alt\">";
}

function roadmap_starter_bootstrap_icon( $name, $classes = '', $tag = 'div' ) {
	$url = roadmap_starter_public_uri( 'images/bootstrap-icons/' . trim( $name ) . '.svg' );

	return "<{$tag} role='img' class='bi {$classes}' style='-webkit-mask-image: url({$url});mask-image: url({$url})'></{$tag}>";
}

function roadmap_starter_fontawesome_icon_svg( object|string $icon, string $style = 'solid', $classes = '', $tag = 'div' ) {
	// The active theme's built (minified) copy, else the parent's source SVG — the parent ships
	// without a public/ build when a child theme is active.
	foreach ( array( roadmap_starter_public_dir( 'images/fontawesome' ), get_template_directory() . '/src/images/fontawesome' ) as $dir ) {
		$file = $dir . '/' . $style . '/' . $icon . '.svg';
		if ( is_readable( $file ) ) {
			return file_get_contents( $file );
		}
	}

	// Block-panel icons only; a missing glyph must not log a warning on every request.
	return '';
}

function roadmap_starter_fontawesome_icon( object|string $icon, string $style = 'solid', $classes = '', $tag = 'div' ) {
	if ( is_string( $icon ) ) {
		$icon = (object) array(
			'id'    => $icon,
			'style' => $style,
		);
	}

	$url = roadmap_starter_public_uri( 'images/fontawesome/' . trim( $icon->style ) . '/' . $icon->id . '.svg' );

	return "<{$tag} role='img' class='icon bi {$classes}' style='-webkit-mask-image: url({$url});mask-image: url({$url})'></{$tag}>";
}
