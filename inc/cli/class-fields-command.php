<?php
/**
 * WP-CLI: `wp roadmap-starter fields audit`.
 *
 * ACF binds a block field by name. A stored name the block's schema does not define binds
 * to nothing: the content sits in the database, the template renders an empty slot, and the
 * page looks structurally fine. Nothing in the editor surfaces it, so it is normally found
 * by someone noticing a missing headline weeks later.
 *
 * This audit compares every stored block field name on every published post against the
 * block's live ACF field tree and reports the strays. Read-only; it exits 1 when it finds
 * anything, so a pipeline can gate on it.
 *
 * Renaming stray names is deliberately not offered here — a rename map is specific to how
 * one site's content went wrong, and the fix belongs upstream in whatever wrote the blocks.
 *
 * @package RoadmapStarter\CLI
 */

declare( strict_types = 1 );

namespace RoadmapStarter\CLI;

use WP_CLI;

final class Fields_Command {

	/**
	 * List stored block field names that the block's ACF schema does not define.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<types>]
	 * : Comma-separated post types to scan. Default: all public types.
	 *
	 * [--format=<format>]
	 * : Output format. Accepts table, csv, json, yaml, count. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp roadmap-starter fields audit
	 *     wp roadmap-starter fields audit --post-type=location --format=json
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function audit( array $args, array $assoc_args ): void {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			WP_CLI::error( 'ACF is not active — there are no block schemas to validate against.' );
		}

		$format = (string) ( $assoc_args['format'] ?? 'table' );
		$hits   = array();
		$blocks = 0;

		foreach ( $this->published_posts( $this->post_types_arg( $assoc_args ) ) as $row ) {
			foreach ( parse_blocks( $row->post_content ) as $block ) {
				$slug = $this->acf_slug( $block );
				if ( '' === $slug ) {
					continue;
				}
				++$blocks;
				foreach ( $this->unknown_paths( $slug, $this->data_of( $block ) ) as $path ) {
					$key                                   = $slug . '|' . $path;
					$hits[ $key ]['block']                 = $slug;
					$hits[ $key ]['stale_name']            = $path;
					$hits[ $key ]['blocks']                = ( $hits[ $key ]['blocks'] ?? 0 ) + 1;
					$hits[ $key ]['types'][ $row->post_type ] = ( $hits[ $key ]['types'][ $row->post_type ] ?? 0 ) + 1;
				}
			}
		}

		uasort( $hits, static fn( array $a, array $b ): int => $b['blocks'] - $a['blocks'] );

		$rows = array();
		foreach ( $hits as $hit ) {
			$rows[] = array(
				'block'      => $hit['block'],
				'stale_name' => $hit['stale_name'],
				'blocks'     => $hit['blocks'],
				'post_types' => $this->count_summary( $hit['types'] ),
			);
		}

		if ( 'json' === $format ) {
			WP_CLI::line(
				(string) wp_json_encode(
					array(
						'blocks_scanned' => $blocks,
						'stale_names'    => $rows,
					)
				)
			);
		} else {
			WP_CLI::log(
				sprintf(
					'Scanned %d ACF blocks. Stale field names: %d distinct, in %d block instance(s).',
					$blocks,
					count( $rows ),
					array_sum( array_column( $rows, 'blocks' ) )
				)
			);
			if ( $rows ) {
				WP_CLI\Utils\format_items( $format, $rows, array( 'block', 'stale_name', 'blocks', 'post_types' ) );
			}
		}

		if ( $rows ) {
			WP_CLI::halt( 1 );
		}
	}

	// ---- schema ---------------------------------------------------------------------------

	/**
	 * A block's field tree straight from ACF: name => sub-tree (empty for leaf fields).
	 *
	 * @return array<string, array>
	 */
	private function schema( string $slug ): array {
		static $cache = array();

		if ( isset( $cache[ $slug ] ) ) {
			return $cache[ $slug ];
		}

		$tree = array();
		foreach ( acf_get_field_groups( array( 'block' => 'acf/' . $slug ) ) as $group ) {
			$tree += $this->tree( (array) acf_get_fields( $group ) );
		}

		$cache[ $slug ] = $tree;

		return $tree;
	}

	/**
	 * @param array<int, array> $fields
	 * @return array<string, array>
	 */
	private function tree( array $fields ): array {
		$out = array();
		foreach ( $fields as $field ) {
			$name = (string) ( $field['name'] ?? '' );
			if ( '' === $name ) {
				continue;
			}
			$out[ $name ] = ! empty( $field['sub_fields'] ) ? $this->tree( (array) $field['sub_fields'] ) : array();
		}

		return $out;
	}

	/**
	 * Distinct stored name-paths (repeater indices removed, joined with '.') that the
	 * block's schema does not define.
	 *
	 * @param array<string, mixed> $data
	 * @return string[]
	 */
	private function unknown_paths( string $slug, array $data ): array {
		$schema  = $this->schema( $slug );
		$unknown = array();

		// A block with no registered fields is either unregistered or field-less; either
		// way there is nothing to compare against, so report nothing rather than everything.
		if ( array() === $schema ) {
			return array();
		}

		foreach ( array_keys( $data ) as $key ) {
			if ( 'ai_content' === $key ) {
				continue;
			}
			$names = array_values( array_filter( $this->tokenize( (string) $key ), 'is_string' ) );
			$node  = $schema;
			$known = true;
			foreach ( $names as $name ) {
				if ( ! is_array( $node ) || ! array_key_exists( $name, $node ) ) {
					$known = false;
					break;
				}
				$node = $node[ $name ];
			}
			if ( ! $known ) {
				$unknown[ implode( '.', $names ) ] = true;
			}
		}

		return array_keys( $unknown );
	}

	/**
	 * Split a stored key into name segments and row indices:
	 * `cards_0_bullets_1_text` → [ 'cards', 0, 'bullets', 1, 'text' ].
	 *
	 * @return array<int, string|int>
	 */
	private function tokenize( string $key ): array {
		$parts  = (array) preg_split( '/_(\d+)_/', $key, -1, PREG_SPLIT_DELIM_CAPTURE );
		$tokens = array();
		foreach ( $parts as $i => $part ) {
			$tokens[] = ( $i % 2 ) ? (int) $part : (string) $part;
		}

		return $tokens;
	}

	// ---- helpers --------------------------------------------------------------------------

	private function acf_slug( array $block ): string {
		$name = (string) ( $block['blockName'] ?? '' );

		return 0 === strpos( $name, 'acf/' ) ? substr( $name, 4 ) : '';
	}

	/**
	 * Block data without the `_name => field_key` reference entries.
	 *
	 * @return array<string, mixed>
	 */
	private function data_of( array $block ): array {
		$data = isset( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] ) ? $block['attrs']['data'] : array();
		foreach ( array_keys( $data ) as $key ) {
			if ( '_' === substr( (string) $key, 0, 1 ) ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * @param array<string, int> $counts
	 */
	private function count_summary( array $counts ): string {
		arsort( $counts );
		$parts = array();
		foreach ( $counts as $label => $n ) {
			$parts[] = $label . '×' . $n;
		}

		return implode( ' ', $parts );
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
	 * Published posts of the given types that contain block markup.
	 *
	 * @param string[] $types
	 * @return object[] Rows with ID, post_type, post_name, post_content.
	 */
	private function published_posts( array $types ): array {
		global $wpdb;

		$types = array_values( array_diff( $types, array( 'revision' ) ) );
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
