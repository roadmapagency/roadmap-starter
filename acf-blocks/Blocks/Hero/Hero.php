<?php namespace RoadmapStarter\Blocks\Hero;

use RoadmapStarter\Blocks\AbstractBlock;

class Hero extends AbstractBlock {

	public function acf_init() {
		if ( function_exists( 'acf_register_block' ) ) {
			acf_register_block(
				array(
					'name'            => $this->get_slug(),
					'title'           => __( 'Hero' ),
					'description'     => __( 'Hero image with large text and optional buttons. Usually used at the top of a page.' ),
					'render_callback' => array( $this, 'render' ),
					'category'        => 'roadmap-starter',
					'icon'            => roadmap_starter_fontawesome_icon_svg( 'panorama' ),
					'keywords'        => array( 'hero', 'image', 'header' ),
					'align'           => 'wide',
					'supports'        => array( 'align' => false ),
					'auto_inline_editing' => true,
					'example'         => array(
						'attributes' => array(
							'mode' => 'preview',
							'data' => array(
								'title'       => 'My great <strong>title</strong>',
								'title_style' => 'h1',
								'subtext'     => 'Some catchy text to grab their attention',
							),
						),
					),
				)
			);
		}
	}

	public function register_fields() {
		$this->field_set->addSelect(
			'theme',
			array(
				'choices'       => array(
					'background-image' => 'Background Image',
					'right-image'      => 'Right Image',
				),
				'default_value' => 'background-image',
			)
		)
						->addSelect(
							'background_theme',
							array(
								'choices'       => array(
									''      => 'None',
									'light' => 'Light',
									'dark'  => 'Dark',
								),
								'default_value' => '',
							)
						)->conditional( 'theme', '==', 'background-image' )
						->addText( 'overline' )
						->addSelect(
							'title_style',
							array(
								'choices'       => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ),
								'default_value' => 'h1',
								'required'      => true,
								'instructions'  => 'Select the heading level for the title. H1 should be used when this is the first block in the layout.',
							)
						)
						->addTextarea(
							'title',
							array(
								'required'     => true,
								'instructions' => 'A short, attention-grabbing title for the hero. Should be about 5-7 words or less.',
							)
						)
						->addWysiwyg(
							'subtext',
							array(
								'toolbar'      => 'very_simple',
								'required'     => true,
								'instructions' => 'A short, attention-grabbing description for the hero. Should be about 1-2 sentences long.',
							)
						)
						->addText( 'primary_button_text', array( 'instructions' => 'The text for the primary button. Should be about 1-2 words long.' ) )
						->addUrl( 'primary_button_url', array( 'instructions' => 'The URL for the primary button.' ) )
						->addText( 'secondary_button_text', array( 'instructions' => 'The text for the secondary button. Should be about 1-2 words long.' ) )
						->addUrl( 'secondary_button_url', array( 'instructions' => 'The URL for the secondary button.' ) )
						->addImage( 'image' )
						->addText( 'image_caption' )
						->addText( 'image_credit' )
						->setLocation( 'block', '==', self::get_acf_slug() );
	}

	public function get_css_classes( $block ) {
		$image = get_field( 'image' );

		return ! $image ? ' ' . self::get_block_class_name() . '__no-image' : '';
	}
}
