<?php

use RoadmapStarter\BlocksServiceProvider;
use Weareroadmap\AiForGutenberg\Agents\BlockFillerAgent;
use Weareroadmap\AiForGutenberg\Agents\PageFillerAgent;

if ( is_plugin_active( 'ai-for-gutenberg/ai-for-gutenberg.php' ) ) {
	add_action( 'acf/init', 'register_blocks_with_ai', 99 );
}

function register_blocks_with_ai() {
	$blocks = BlocksServiceProvider::get_instance();
	foreach ( $blocks->get_block_names() as $block_name ) {
		// skip coverage block
		if ( $block_name === 'acf/coverage' ) {
			continue;
		}

		// TODO put the actual schema here
		$block_data   = acf_get_block_type( $block_name );
		$field_groups = acf_get_field_groups( array( 'block' => $block_name ) );
		$schema       = array(
			'type'        => 'object',
			'description' => $block_data['description'],
			'properties'  => array(),
		);

		if ( ! empty( $field_groups ) ) {
			foreach ( $field_groups as $field_group ) {
				$fields = acf_get_fields( $field_group );
				foreach ( $fields as $field ) {
					// Don't include AI fields in the schema
					if ( $field['name'] === 'ai_content' ) {
						continue;
					}
					$schema['additionalProperties']         = false;
					$schema['properties'][ $field['name'] ] = process_acf_field( $field );
					$schema['required'][]                   = $field['name'];
				}
			}
		}

		add_filter(
			'ai4g_register_block_with_ai',
			function ( $registrations ) use ( $block_name, $schema ) {
				$registrations[] = array(
					'block_id' => $block_name,
					'schema'   => $schema,
				);
				return $registrations;
			}
		);
	}
}

function process_acf_field( $field ) {
	$description = isset( $field['instructions'] ) ? $field['instructions'] : '';

	// Handle different field types
	switch ( $field['type'] ) {
		case 'repeater':
			$sub_fields = isset( $field['sub_fields'] ) ? $field['sub_fields'] : array();
			$items      = array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(),
			);

			foreach ( $sub_fields as $sub_field ) {
				$items['properties'][ $sub_field['name'] ] = process_acf_field( $sub_field );
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
				$properties[ $sub_field['name'] ] = process_acf_field( $sub_field );
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
				'description'          => 'A Font Awesome icon. Use the icon_search tool to find the right icon by concept (e.g. "shield" for protection). Do not use the fa- prefix.',
				'properties'           => array(
					'style'   => array( 'type' => 'string' ),
					'id'      => array( 'type' => 'string' ),
					'label'   => array( 'type' => 'string' ),
					'unicode' => array( 'type' => 'string' ),
				),
				'required'             => array( 'style', 'id', 'label', 'unicode' ),
				'additionalProperties' => false,
			);

		// Default to string for text, textarea, wysiwyg, url, email, etc.
		default:
			return array(
				'type'        => 'string',
				'description' => $description,
			);
	}
}


if ( class_exists( 'Weareroadmap\AiForGutenberg\Vendor\NeuronAI\Tools\Tool' ) ) {
	require_once __DIR__ . '/FontAwesomeTool.php';

	// Add FontAwesomeTool to BlockFillerAgent (used for block swapping)
	add_filter(
		'ai4g_filter_tools_' . BlockFillerAgent::class,
		function ( $tools, BlockFillerAgent $agent ) {
			if ( str_starts_with( $agent->getBlockId(), 'acf/' ) ) {
				$tools[] = new FontAwesomeTool();
			}

			return $tools;
		},
		10,
		2
	);

	// Add FontAwesomeTool to PageFillerAgent (used for full page generation)
	add_filter(
		'ai4g_filter_tools_' . PageFillerAgent::class,
		function ( $tools ) {
			$tools[] = new FontAwesomeTool();
			return $tools;
		}
	);
}
