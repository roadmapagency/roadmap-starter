<?php

use RoadmapStarter\BlocksServiceProvider;
use Weareroadmap\AiForGutenberg\Agents\BlockFillerAgent;
use Weareroadmap\AiForGutenberg\Vendor\NeuronAI\Tools\Tool;
use Weareroadmap\AiForGutenberg\Vendor\NeuronAI\Tools\ToolProperty;
use Weareroadmap\AiForGutenberg\Vendor\NeuronAI\SystemPrompt;

class FontAwesomeTool extends Tool {

	public function __construct() {
		$prompt = 'Search the Font Awesome free icon library by concept or keyword. Use descriptive concepts (e.g. "shield" for protection, "rocket" for speed, "handshake" for partnership) rather than the exact text being displayed.';

		parent::__construct(
			'icon_search',
			$prompt,
		);

		$this->initTool();
	}

	public function initTool() {
		$this->addProperty(
			new ToolProperty(
				name: 'name',
				type: 'string',
				description: 'A conceptual keyword describing the icon (e.g. "shield", "rocket", "check-circle"). Do NOT use the literal text being displayed — think about the visual concept it represents. Do not include the "fa-" prefix.',
				required: true
			)
		);

		$this->setCallable(
			function ( string $name ) {
				// Strip leading "fa-" or "fa " prefix if present, without mangling icon names
				$name = trim( preg_replace( '/^fa[- ]/i', '', $name ) );

				// make a wp_remote_get to the free icon set font awesome api
				$body = <<<EOF
                    query{
                        search(version: "6.x", query: "{$name}", first: 5) {
                            id
                            label
                            unicode
                            familyStylesByLicense{
                            free{
                                family
                                prefix
                                style
                            }
                            }
                        }
                    }
                EOF;

				$remote_post = wp_remote_post(
					'https://api.fontawesome.com/v6.0.0/icons',
					array(
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 30,
						'body'    => json_encode( array( 'query' => $body ) ),
					)
				);

				$icon = null;
				if ( ! is_wp_error( $remote_post ) ) {
					$response_json = wp_remote_retrieve_body( $remote_post );
					$result        = json_decode( $response_json, true );

					// Loop through all search results to find one with a free license
					if ( ! empty( $result['data']['search'] ) ) {
						foreach ( $result['data']['search'] as $iconData ) {
							// Check if this icon has a free license
							if ( ! empty( $iconData['familyStylesByLicense']['free'][0] ) ) {
								$icon = array(
									'style'   => $iconData['familyStylesByLicense']['free'][0]['style'],
									'id'      => $iconData['id'],
									'label'   => $iconData['label'],
									'unicode' => $iconData['unicode'],
								);
								break; // Found a free icon, stop looking
							}
						}
					}
				}

				if ( empty( $icon ) ) {
					$icon = array(
						'style'   => 'solid',
						'id'      => 'check',
						'label'   => 'Check',
						'unicode' => 'f00c',
					);
				} else {
					error_log( 'FontAwesomeTool: icon found: ' . print_r( $icon, true ) );
				}

				return json_encode( $icon );
			}
		);
	}
}
