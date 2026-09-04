<?php
/**
 * Synced patterns ("Reusable Blocks") — resolution helpers.
 *
 * A section the design renders unchanged on many routes should exist once, as a synced
 * pattern (`wp_block` post), with every page referencing it via
 * `<!-- wp:block {"ref":N} /-->`. Copying such a section into each post is how content
 * that was approved once ends up drifting into several variants, and how a one-line copy
 * change becomes an edit in ninety places.
 *
 * These helpers are deliberately generic: the theme knows how to *resolve* a pattern by
 * slug, not which patterns a given site has. The pattern posts themselves are created by
 * the conversion pipeline (or by hand in the editor); their content lives in the database,
 * never in theme code.
 *
 * A rigid CPT's locked block template refers to a pattern by post ID, which is not known
 * until the pattern exists, so build those rows with roadmap_starter_pattern_row():
 *
 *     'template' => array(
 *         array( 'acf/hero', array() ),
 *         roadmap_starter_pattern_row(
 *             'referrers-band',
 *             array( 'acf/callout', array( 'data' => array( 'variant' => 'dark' ) ) )
 *         ),
 *     ),
 *
 * Before the pattern exists the inline fallback row is used, so a freshly deployed site
 * keeps working; once it exists the slot becomes the synced pattern. With
 * `template_lock => 'all'` the editor shows it locked in place and editors change the
 * shared copy through "Edit original", which updates every page at once.
 *
 * Audit duplicated section content with `wp roadmap-starter patterns audit`.
 *
 * @package RoadmapStarter
 */

declare( strict_types = 1 );

/**
 * The `wp_block` post for a pattern slug, in any status, or null when there is none.
 */
function roadmap_starter_pattern_post( string $slug ): ?WP_Post {
	$post = get_page_by_path( $slug, OBJECT, 'wp_block' );

	return $post instanceof WP_Post ? $post : null;
}

/**
 * Post ID of the published synced pattern for $slug, or 0 when it does not exist yet.
 *
 * Cached per request: block templates consult this on every `init`.
 */
function roadmap_starter_pattern_ref( string $slug ): int {
	static $cache = array();

	if ( array_key_exists( $slug, $cache ) ) {
		return $cache[ $slug ];
	}

	$post           = roadmap_starter_pattern_post( $slug );
	$cache[ $slug ] = ( $post && 'publish' === $post->post_status ) ? (int) $post->ID : 0;

	return $cache[ $slug ];
}

/**
 * A block-template row for a shared section: the synced pattern when it exists, else the
 * inline ACF row so the template still renders on a site whose patterns are not created yet.
 *
 * @param string $slug         Pattern slug (the `wp_block` post_name).
 * @param array  $fallback_row A `array( 'acf/<block>', array( 'data' => … ) )` template row.
 * @return array The template row to register.
 */
function roadmap_starter_pattern_row( string $slug, array $fallback_row ): array {
	$ref = roadmap_starter_pattern_ref( $slug );

	return $ref > 0 ? array( 'core/block', array( 'ref' => $ref ) ) : $fallback_row;
}

/**
 * Normalise a stored field value for comparison: strip tags, decode entities (including
 * non-breaking spaces), collapse whitespace, lower-case. Used by the duplicate audit so
 * that copies differing only in markup or spacing are recognised as the same content.
 */
function roadmap_starter_pattern_normalize( string $value ): string {
	$value = wp_strip_all_tags( $value );
	$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$value = str_replace( array( "\xC2\xA0", '&nbsp;' ), ' ', $value );
	$value = (string) preg_replace( '/\s+/u', ' ', $value );

	return mb_strtolower( trim( $value ) );
}
