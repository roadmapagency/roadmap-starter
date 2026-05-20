<?php
/**
 * Push this theme's ACF block schemas into the AI by Roadmap plugin, and
 * register the theme-specific `roadmap-starter/search-icons` ability so
 * agents can find Font Awesome icons by concept.
 *
 * The old `ai4g_register_block_with_ai` / `ai4g_filter_tools_*` filters from
 * the ai-for-gutenberg plugin are gone — abilities now serve the same role
 * for any LLM caller (our own agents, MCP, REST).
 */

use RoadmapStarter\BlocksServiceProvider;

add_action( 'acf/init', 'roadmap_starter_register_blocks_with_ai', 99 );
add_action( 'wp_abilities_api_categories_init', 'roadmap_starter_register_ability_category' );
add_action( 'wp_abilities_api_init', 'roadmap_starter_register_icon_ability' );

function roadmap_starter_register_ability_category() {
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}
	wp_register_ability_category(
		'roadmap-starter',
		array(
			'label'       => __( 'Roadmap Starter', 'roadmap-starter' ),
			'description' => __( 'Theme-specific abilities contributed by the Roadmap Starter theme.', 'roadmap-starter' ),
		)
	);
}

function roadmap_starter_register_blocks_with_ai() {
	$blocks = BlocksServiceProvider::get_instance();
	foreach ( $blocks->get_block_names() as $block_name ) {
		// Skip the coverage block — it's reporting-only, not user-facing.
		if ( $block_name === 'acf/coverage' ) {
			continue;
		}

		$block_data   = acf_get_block_type( $block_name );
		$field_groups = acf_get_field_groups( array( 'block' => $block_name ) );
		$schema       = array(
			'type'        => 'object',
			'description' => $block_data['description'] ?? '',
			'properties'  => array(),
		);

		if ( ! empty( $field_groups ) ) {
			foreach ( $field_groups as $field_group ) {
				$fields = acf_get_fields( $field_group );
				foreach ( $fields as $field ) {
					if ( $field['name'] === 'ai_content' ) {
						continue;
					}
					$schema['additionalProperties']         = false;
					$schema['properties'][ $field['name'] ] = roadmap_starter_process_acf_field( $field );
					$schema['required'][]                   = $field['name'];
				}
			}
		}

		add_filter(
			'ai_by_roadmap_register_block',
			static function ( $registrations ) use ( $block_name, $schema ) {
				$registrations[] = array(
					'block_id' => $block_name,
					'schema'   => $schema,
				);
				return $registrations;
			}
		);
	}
}

function roadmap_starter_process_acf_field( $field ) {
	$description = isset( $field['instructions'] ) ? $field['instructions'] : '';

	switch ( $field['type'] ) {
		case 'repeater':
			$sub_fields = isset( $field['sub_fields'] ) ? $field['sub_fields'] : array();
			$items      = array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(),
			);

			foreach ( $sub_fields as $sub_field ) {
				$items['properties'][ $sub_field['name'] ] = roadmap_starter_process_acf_field( $sub_field );
			}

			$items['additionalProperties'] = false;
			$items['required']             = array_keys( $items['properties'] );
			return array(
				'type'        => 'array',
				'description' => $description,
				'items'       => $items,
			);

		case 'group':
			$sub_fields = isset( $field['sub_fields'] ) ? $field['sub_fields'] : array();
			$properties = array();

			foreach ( $sub_fields as $sub_field ) {
				$properties[ $sub_field['name'] ] = roadmap_starter_process_acf_field( $sub_field );
			}

			return array(
				'type'                 => 'object',
				'description'          => $description,
				'additionalProperties' => false,
				'required'             => array_keys( $properties ),
				'properties'           => $properties,
			);

		case 'select':
		case 'radio':
		case 'button_group':
			$choices = isset( $field['choices'] ) ? array_keys( $field['choices'] ) : array();
			return array(
				'type'        => 'string',
				'description' => $description,
				'enum'        => $choices,
			);

		case 'checkbox':
			$choices = isset( $field['choices'] ) ? array_keys( $field['choices'] ) : array();
			return array(
				'type'        => 'array',
				'description' => $description,
				'items'       => array(
					'type' => 'string',
					'enum' => $choices,
				),
			);

		case 'true_false':
			return array(
				'type'        => 'boolean',
				'description' => $description,
			);

		case 'number':
			return array(
				'type'        => 'number',
				'description' => $description,
			);

		case 'range':
			$min    = isset( $field['min'] ) ? $field['min'] : null;
			$max    = isset( $field['max'] ) ? $field['max'] : null;
			$schema = array(
				'type'        => 'number',
				'description' => $description,
			);

			if ( $min !== null ) {
				$schema['minimum'] = $min;
			}
			if ( $max !== null ) {
				$schema['maximum'] = $max;
			}

			return $schema;

		case 'font-awesome':
			return array(
				'type'                 => 'object',
				'description'          => 'A Font Awesome icon. Call the roadmap-starter/search-icons ability to find the right icon by concept (e.g. "shield" for protection). Do not use the fa- prefix when querying.',
				'properties'           => array(
					'style'   => array( 'type' => 'string' ),
					'id'      => array( 'type' => 'string' ),
					'label'   => array( 'type' => 'string' ),
					'unicode' => array( 'type' => 'string' ),
				),
				'required'             => array( 'style', 'id', 'label', 'unicode' ),
				'additionalProperties' => false,
			);

		default:
			return array(
				'type'        => 'string',
				'description' => $description,
			);
	}
}

/**
 * Register `roadmap-starter/search-icons` so any caller — Claude Desktop,
 * curl, or our own BlockFillerAgent — can find Font Awesome icons by
 * concept. PageFillerAgent and BlockFillerAgent pick it up via the
 * ai_by_roadmap_filter_tools filter below.
 */
function roadmap_starter_register_icon_ability() {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	wp_register_ability(
		'roadmap-starter/search-icons',
		array(
			'category'            => 'roadmap-starter',
			'label'               => __( 'Search Font Awesome icons', 'roadmap-starter' ),
			'description'         => __( 'Find a Font Awesome icon by concept. Search using a visual concept (e.g. "shield" for protection, "rocket" for speed) rather than the literal text. Do not include the "fa-" prefix. Returns the icon style, ID, label, and unicode.', 'roadmap-starter' ),
			'input_schema'        => array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => array( 'query' ),
				'properties'           => array(
					'query' => array(
						'type'        => 'string',
						'description' => 'A conceptual keyword for the icon (e.g. "shield", "rocket", "check-circle"). No "fa-" prefix.',
					),
				),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => array( 'style', 'id', 'label', 'unicode' ),
				'properties'           => array(
					'style'   => array( 'type' => 'string' ),
					'id'      => array( 'type' => 'string' ),
					'label'   => array( 'type' => 'string' ),
					'unicode' => array( 'type' => 'string' ),
				),
			),
			'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
			'execute_callback'    => 'roadmap_starter_search_icons',
		)
	);
}

function roadmap_starter_search_icons( array $input ) {
	$name = trim( preg_replace( '/^fa[- ]/i', '', (string) $input['query'] ) );

	$body = <<<GRAPHQL
        query {
            search(version: "6.x", query: "{$name}", first: 5) {
                id
                label
                unicode
                familyStylesByLicense {
                    free {
                        family
                        prefix
                        style
                    }
                }
            }
        }
GRAPHQL;

	$remote = wp_remote_post(
		'https://api.fontawesome.com/v6.0.0/icons',
		array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 30,
			'body'    => wp_json_encode( array( 'query' => $body ) ),
		)
	);

	if ( ! is_wp_error( $remote ) ) {
		$result = json_decode( wp_remote_retrieve_body( $remote ), true );
		if ( ! empty( $result['data']['search'] ) ) {
			foreach ( $result['data']['search'] as $iconData ) {
				if ( ! empty( $iconData['familyStylesByLicense']['free'][0] ) ) {
					return array(
						'style'   => $iconData['familyStylesByLicense']['free'][0]['style'],
						'id'      => $iconData['id'],
						'label'   => $iconData['label'],
						'unicode' => $iconData['unicode'],
					);
				}
			}
		}
	}

	// Sensible fallback so the LLM always gets a usable response.
	return array(
		'style'   => 'solid',
		'id'      => 'check',
		'label'   => 'Check',
		'unicode' => 'f00c',
	);
}

/**
 * Make the icon ability available as a tool to the page-filler and
 * block-filler agents. The plugin reads this filter when assembling each
 * agent's tool list.
 */
add_filter(
	'ai_by_roadmap_filter_tools_Roadmap\\AiByRoadmap\\Blocks\\Agents\\PageFillerAgent',
	static function ( array $tools ): array {
		$tools[] = 'roadmap-starter/search-icons';
		return $tools;
	}
);

add_filter(
	'ai_by_roadmap_filter_tools_Roadmap\\AiByRoadmap\\Blocks\\Agents\\BlockFillerAgent',
	static function ( array $tools, $agent ): array {
		if ( str_starts_with( $agent->block_id(), 'acf/' ) ) {
			$tools[] = 'roadmap-starter/search-icons';
		}
		return $tools;
	},
	10,
	2
);
