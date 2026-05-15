<?php namespace RoadmapStarter\Blocks\ImageAndText;

use RoadmapStarter\Blocks\AbstractBlock;

class ImageAndText extends AbstractBlock {

	public function acf_init() {
		if ( function_exists( 'acf_register_block' ) ) {
			acf_register_block(
				array(
					'name'            => $this->get_slug(),
					'title'           => __( 'Image and Text' ),
					'description'     => __( 'Image and text blocks with a top heading and description. Usually used to highlight a feature or benefit.' ),
					'render_callback' => array( $this, 'render' ),
					'category'        => 'roadmap-starter',
					'icon'            => roadmap_starter_fontawesome_icon_svg( 'id-card', 'regular' ),
					'keywords'        => array( 'image', 'text' ),
					'align'           => 'wide',
					'example'         => array(
						'attributes' => array(
							'mode' => 'preview',
							'data' => array(
								'theme'                 => 'white',
								'overline'              => 'Today Only!',
								'title'                 => 'Get Started Today',
								'description'           => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui. Nullam vel eros sit amet arcu vestibulum accumsan in in leo. Nullam vel eros sit amet arcu vestibulum accumsan in in leo. Nullam vel eros sit amet arcu vestibulum accumsan in in leo.</p>',
								'primary_button_text'   => 'Buy Now',
								'primary_button_url'    => '#',
								'secondary_button_text' => 'Learn More',
								'secondary_button_url'  => '#',
								'image'                 => get_post( 60604, 'ARRAY_A' ),
							),
						),
					),
				)
			);
		}
	}

	public function register_fields() {
		$this->field_set->addField(
			'image',
			'image_aspect_ratio_crop',
			array(
				'aspect_ratio_width'  => 1000,
				'aspect_ratio_height' => 740,
				'required'            => true,
			)
		)
						->conditional( 'theme', '!=', 'circle' )
						->addField(
							'image_circle',
							'image_aspect_ratio_crop',
							array(
								'aspect_ratio_width'  => 1000,
								'aspect_ratio_height' => 1000,
								'required'            => true,
							)
						)
						->conditional( 'theme', '==', 'circle' )
						->addSelect( 'image_position', array( 'choices' => array( 'left', 'right' ) ) )
						->addText( 'overline' )
						->addText( 'title', array( 'required' => true ) )
						->addWysiwyg(
							'description',
							array(
								'toolbar'  => 'simple',
								'required' => true,
							)
						)
						->addText( 'primary_button_text' )
						->addUrl( 'primary_button_url' )
						->addText( 'secondary_button_text' )
						->addUrl( 'secondary_button_url' )
						->addSelect(
							'theme',
							array(
								'choices' => array(
									'white'       => 'White',
									'green'       => 'Green',
									'light-green' => 'Light Green',
									'circle'      => 'Circle',
								),
							)
						)
						->addTrueFalse( 'remove_angles', array( 'default_value' => 0 ) )
						->setLocation( 'block', '==', self::get_acf_slug() );
	}
}
