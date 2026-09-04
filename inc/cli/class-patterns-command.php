<?php
/**
 * WP-CLI: `wp roadmap-starter patterns audit`.
 *
 * Finds section content that is duplicated across published posts — the signature of a
 * section that should be one synced pattern rather than N copies.
 *
 * Two things are reported, and the second matters more:
 *
 *  - **Exact duplicates**: byte-identical block content on several posts. Candidates for a
 *    synced pattern; informational, since some repetition is legitimate.
 *  - **Near-duplicates**: same block type and same heading, but differing fields. That is
 *    content drift — one approved section that has quietly become several versions — and it
 *    is what makes the audit exit non-zero.
 *
 * Read-only. Creating patterns and rewriting posts to reference them is a content operation,
 * not a theme concern; see roadmap_starter_pattern_row() in inc/patterns.php for how a locked
 * CPT template consumes one.
 *
 * @package RoadmapStarter\CLI
 */

declare( strict_types = 1 );

namespace RoadmapStarter\CLI;

use WP_CLI;

final class Patterns_Command {

	/**
	 * Report block content duplicated across published posts.
	 *
	 * ## OPTIONS
	 *
	 * [--min=<n>]
	 * : Only report groups shared by at least this many posts. Default 3.
	 *
	 * [--post-type=<types>]
	 * : Comma-separated post types to scan. Default: all public types.
	 *
	 * [--format=<format>]
	 * : Output format. Accepts table, csv, json, yaml, count. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp roadmap-starter patterns audit
	 *     wp roadmap-starter patterns audit --min=2 --format=json
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function audit( array $args, array $assoc_args ): void {
		$min    = max( 2, (int) ( $assoc_args['min'] ?? 3 ) );
		$format = (string) ( $assoc_args['format'] ?? 'table' );

		$exact = array();
		$near  = array();
		$total = 0;

		foreach ( $this->published_posts( $this->post_types_arg( $assoc_args ) ) as $row ) {
			foreach ( parse_blocks( $row->post_content ) as $block ) {
				$name = (string) ( $block['blockName'] ?? '' );
				if ( 0 !== strpos( $name, 'acf/' ) ) {
					continue;
				}
				++$total;

				$data = $this->block_fields( $block );
				ksort( $data );
				$label = $this->block_label( $data );
				$post  = $row->post_type . ':' . $row->post_name;
				$hash  = md5( $name . (string) wp_json_encode( $data ) );

				$exact[ $hash ]['block']          = $name;
				$exact[ $hash ]['label']          = $label;
				$exact[ $hash ]['posts'][ $post ] = true;

				if ( '' !== $label ) {
					$key                            = $name . '|' . roadmap_starter_pattern_normalize( $label );
					$near[ $key ]['block']          = $name;
					$near[ $key ]['label']          = $label;
					$near[ $key ]['variants'][ $hash ] = $data;
					$near[ $key ]['posts'][ $post ] = true;
				}
			}
		}

		$exact = array_filter( $exact, static fn( array $g ): bool => count( $g['posts'] ) >= $min );
		uasort( $exact, static fn( array $a, array $b ): int => count( $b['posts'] ) - count( $a['posts'] ) );

		$exact_rows = array();
		foreach ( $exact as $group ) {
			$exact_rows[] = array(
				'posts'      => count( $group['posts'] ),
				'block'      => substr( $group['block'], 4 ),
				'heading'    => mb_substr( $group['label'], 0, 60 ),
				'post_types' => $this->type_summary( array_keys( $group['posts'] ) ),
			);
		}

		$near = array_filter(
			$near,
			static fn( array $g ): bool => count( $g['variants'] ) > 1 && count( $g['posts'] ) >= $min
		);
		uasort( $near, static fn( array $a, array $b ): int => count( $b['posts'] ) - count( $a['posts'] ) );

		$near_rows = array();
		foreach ( $near as $group ) {
			$near_rows[] = array(
				'posts'    => count( $group['posts'] ),
				'variants' => count( $group['variants'] ),
				'block'    => substr( $group['block'], 4 ),
				'heading'  => mb_substr( $group['label'], 0, 50 ),
				'differs'  => implode( ',', $this->differing_fields( array_values( $group['variants'] ) ) ),
			);
		}

		if ( 'json' === $format ) {
			WP_CLI::line(
				(string) wp_json_encode(
					array(
						'blocks_scanned'  => $total,
						'min'             => $min,
						'exact_duplicates' => $exact_rows,
						'near_duplicates'  => $near_rows,
					)
				)
			);
		} else {
			WP_CLI::log(
				sprintf(
					'Scanned %d ACF blocks. Identical-content groups shared by >= %d posts: %d',
					$total,
					$min,
					count( $exact_rows )
				)
			);
			if ( $exact_rows ) {
				WP_CLI\Utils\format_items( $format, $exact_rows, array( 'posts', 'block', 'heading', 'post_types' ) );
			}

			WP_CLI::log( '' );
			WP_CLI::log(
				sprintf(
					'Near-duplicates (same block + heading, differing fields — content drift): %d',
					count( $near_rows )
				)
			);
			if ( $near_rows ) {
				WP_CLI\Utils\format_items( $format, $near_rows, array( 'posts', 'variants', 'block', 'heading', 'differs' ) );
			}
		}

		if ( $near_rows ) {
			WP_CLI::halt( 1 );
		}
	}

	// ---- helpers --------------------------------------------------------------------------

	/**
	 * A block's field values, minus ACF key references and the hidden source marker.
	 *
	 * @return array<string, mixed>
	 */
	private function block_fields( array $block ): array {
		$data = isset( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] ) ? $block['attrs']['data'] : array();
		foreach ( array_keys( $data ) as $key ) {
			if ( '_' === substr( (string) $key, 0, 1 ) || 'ai_content' === $key ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * The most heading-like value in a block, used to group variants of one section.
	 *
	 * @param array<string, mixed> $data
	 */
	private function block_label( array $data ): string {
		foreach ( array( 'heading', 'title', 'text', 'quote' ) as $key ) {
			if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
				return trim( wp_strip_all_tags( $data[ $key ] ) );
			}
		}

		return '';
	}

	/**
	 * @param string[] $post_keys "post_type:post_name" strings.
	 */
	private function type_summary( array $post_keys ): string {
		$counts = array_count_values( array_map( static fn( string $k ): string => explode( ':', $k )[0], $post_keys ) );
		arsort( $counts );
		$parts = array();
		foreach ( $counts as $type => $n ) {
			$parts[] = $type . '×' . $n;
		}

		return implode( ' ', $parts );
	}

	/**
	 * Field names whose values are not the same across every variant of a section.
	 *
	 * @param array[] $variants
	 * @return string[]
	 */
	private function differing_fields( array $variants ): array {
		$keys = array();
		foreach ( $variants as $variant ) {
			$keys = array_merge( $keys, array_keys( $variant ) );
		}

		$differs = array();
		foreach ( array_unique( $keys ) as $key ) {
			$values = array_unique(
				array_map( static fn( array $v ): string => (string) wp_json_encode( $v[ $key ] ?? null ), $variants )
			);
			if ( count( $values ) > 1 ) {
				$differs[] = $key;
			}
		}

		return $differs;
	}

	/**
	 * @return string[]
	 */
	private function post_types_arg( array $assoc_args ): array {
		if ( ! empty( $assoc_args['post-type'] ) ) {
			return array_map( 'trim', explode( ',', (string) $assoc_args['post-type'] ) );
		}

		return array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
	}

	/**
	 * Published posts of the given types that contain block markup. `wp_block` posts are
	 * excluded: a pattern's own content is the single copy, not a duplicate of itself.
	 *
	 * @param string[] $types
	 * @return object[] Rows with ID, post_type, post_name, post_content.
	 */
	private function published_posts( array $types ): array {
		global $wpdb;

		$types = array_values( array_diff( $types, array( 'wp_block', 'revision' ) ) );
		if ( ! $types ) {
			return array();
		}

		$in = implode( ',', array_fill( 0, count( $types ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_type, post_name, post_content FROM {$wpdb->posts}
				 WHERE post_status = 'publish' AND post_type IN ($in) AND post_content LIKE '%<!-- wp:acf/%'
				 ORDER BY ID",
				$types
			)
		);
	}
}
