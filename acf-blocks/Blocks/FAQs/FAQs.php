<?php namespace RoadmapStarter\Blocks\FAQs;

use RoadmapStarter\Blocks\AbstractBlock;

class FAQs extends AbstractBlock {

	public function acf_init() {
		if ( function_exists( 'acf_register_block' ) ) {
			acf_register_block(
				array(
					'name'            => $this->get_slug(),
					'title'           => __( 'FAQs' ),
					'description'     => __( 'Frequently asked questions (FAQs)' ),
					'render_callback' => array( $this, 'render' ),
					'category'        => 'roadmap-starter',
					'icon'            => roadmap_starter_fontawesome_icon_svg( 'circle-question', 'regular' ),
					'keywords'        => array( 'FAQs', 'FAQ', 'questions' ),
					'align'           => 'wide',
					'example'         => array(
						'attributes' => array(
							'mode' => 'preview',
							'data' => array(
								'categories' => array(
									array(
										'category'  => 'General',
										'questions' => array(
											array(
												'question' => 'What is the meaning of life?',
												'answer'   => 'The meaning of life is 42.',
											),
											array(
												'question' => 'What is the airspeed velocity of an unladen swallow?',
												'answer'   => 'What do you mean? An African or European swallow?',
											),
										),
									),
									array(
										'category'  => 'Billing',
										'questions' => array(
											array(
												'question' => 'How do I pay my bill?',
												'answer'   => 'You can pay your bill online, by phone, or by mail.',
											),
											array(
												'question' => 'What happens if I don\'t pay my bill?',
												'answer'   => 'You will be charged a late fee and your service may be interrupted.',
											),
										),
									),
								),
							),
						),
					),
				)
			);
		}
	}

	public function register_fields() {
		$this->field_set
						->addText( 'title' )
						->addRepeater(
							'categories',
							array(
								'layout'       => 'block',
								'instructions' => 'Add a category for each group of FAQs.',
							)
						)
						->addText( 'category' )
						->addRepeater(
							'questions',
							array(
								'layout'       => 'block',
								'instructions' => 'A list of questions related to the category.',
							)
						)
						->addText( 'question', array( 'instructions' => 'The questions being asked.' ) )
						->addWysiwyg(
							'answer',
							array(
								'toolbar'      => 'very_simple',
								'instructions' => 'The answer to the question.',
							)
						)
						->endRepeater()
						->endRepeater()
						->setLocation( 'block', '==', self::get_acf_slug() );
	}
}
