<?php
/**
 * Single source of truth for the theme's identity.
 *
 * The values here come from {@see wp_get_theme()} (which reads style.css),
 * so a fresh clone of this theme only needs to update style.css and everything
 * derived from these helpers follows automatically — block CSS class prefixes,
 * enqueue handles, the block category slug, the AI ability category, etc.
 *
 * The text-domain literal (the second argument to `__()`, `_e()`, `_x()` etc.)
 * is intentionally NOT derived from here. WordPress's translation-extraction
 * tooling requires literal strings, so we leave those alone. The clone tool
 * handles the text-domain rewrite at production-clone time when needed.
 *
 * @package RoadmapStarter
 */

declare( strict_types = 1 );

namespace RoadmapStarter;

final class Identity {

	/**
	 * The stylesheet (directory) slug for the active theme.
	 *
	 * Matches the wp-content/themes/{slug}/ directory name. This is the
	 * single value that determines the theme's identity at runtime — clones
	 * get a different slug here because they live in a different directory.
	 */
	public static function slug(): string {
		return get_stylesheet();
	}

	/**
	 * The active theme's text domain, as declared in style.css.
	 *
	 * Use this only for non-translation contexts (e.g. asset handles, category
	 * slugs). Translation calls must use literal strings.
	 */
	public static function textdomain(): string {
		return (string) wp_get_theme()->get( 'TextDomain' );
	}

	/**
	 * The active theme's human-readable name, as declared in style.css.
	 */
	public static function name(): string {
		return (string) wp_get_theme()->get( 'Name' );
	}

	/**
	 * Function-prefix-safe version of the slug.
	 *
	 * Slugs use hyphens (e.g. `client-acme`); PHP function names use
	 * underscores. This helper is useful when building dynamic hook names
	 * or option keys.
	 */
	public static function function_prefix(): string {
		return str_replace( '-', '_', self::slug() );
	}

	/**
	 * Prefix for block CSS classes: `wp-block-{prefix}` / `wp-block-{prefix}-{block}`.
	 *
	 * Defaults to the active theme's directory, so existing sites keep their classes. A child theme
	 * pins it with the `roadmap_starter_block_prefix` filter (and `$block-prefix` in its SCSS) so a
	 * directory rename can never desync markup from styles.
	 */
	public static function block_prefix(): string {
		return (string) apply_filters( 'roadmap_starter_block_prefix', self::slug() );
	}

	/**
	 * PHP namespace of the active (child) theme's blocks: `{namespace}\{Block}\{Block}` in
	 * acf-blocks/Blocks/{Block}/{Block}.php. Filter `roadmap_starter_block_namespace`.
	 */
	public static function block_namespace(): string {
		return trim( (string) apply_filters( 'roadmap_starter_block_namespace', 'RoadmapStarter\\Blocks' ), '\\' );
	}
}
