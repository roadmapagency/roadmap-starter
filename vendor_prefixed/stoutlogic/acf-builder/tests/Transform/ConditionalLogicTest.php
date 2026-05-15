<?php

namespace RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Tests\Transform;

use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder;
use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldBuilder;
use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\ConditionalBuilder;
use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Transform;

class ConditionalLogicTest extends \PHPUnit_Framework_TestCase
{
    public function testIsIterative()
    {
        $builder = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder');
        $transform = new Transform\ConditionalLogic($builder->reveal());
        $this->assertInstanceOf('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Transform\IterativeTransform', $transform);
    }

    public function testGetKeys()
    {
        $builder = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder');
        $transform = new Transform\ConditionalLogic($builder->reveal());
        $this->assertSame(['conditional_logic'], $transform->getKeys());
    }

    public function testTransformValue()
    {
        $field = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldBuilder');
        $field
            ->getKey()
            ->willReturn('field_name');
        $field
            ->hasCustomKey()
            ->willReturn(false);


        $builder = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder');
        $builder
            ->getField('name')
            ->willReturn($field->reveal());

        $builder
            ->fieldExists('name')
            ->willReturn(true);

        $transform = new Transform\ConditionalLogic($builder->reveal());
        $this->assertSame([[[
            'field' => 'field_name',
            'operator' => '==',
            'value' => 1,
        ]]], $transform->transformValue([[[
            'field' => 'name',
            'operator' => '==',
            'value' => 1,
        ]]]));
    }

    public function testOnlyGetsAppliedOncePerLevel()
    {
        $builder = new \RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FlexibleContentBuilder('name');
        $builder
            ->addLayout('hero')
                ->addImage( 'hero_image' )
                ->addWysiwyg( 'hero_text' )
                ->addRepeater('cta')
                    ->addSelect('link_type')
                        ->addChoices('internal', 'external', 'text')
                    ->addPageLink('cta_link', [
                        'post_type' => [
                            'page',
                            'post',
                            'product',
                        ],
                    ])
                        ->conditional('link_type', '==', 'internal');


        $expectedConfig = [
            'layouts' => [
                [
                    'name' => 'hero',
                    'sub_fields' => [
                        [
                            'name' => 'hero_image'
                        ],
                        [
                            'name' => 'hero_text'
                        ],
                        [
                            'name' => 'cta',
                            'sub_fields' => [
                                [
                                    'key' => 'field_name_hero_cta_link_type',
                                    'name' => 'link_type'
                                ],
                                [
                                    'key' => 'field_name_hero_cta_cta_link',
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'field_name_hero_cta_link_type',
                                            ]
                                        ]
                                    ]

                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $config = $builder->build();
        $this->assertArraySubset($expectedConfig, $config);
    }


    public function testAllowConditionBasedOnParentFieldWithCustomKey()
    {
        $builder = new \RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FlexibleContentBuilder('name');
        $builder
            ->addLayout('hero')
            ->addSelect('hero_type')
            ->addChoices('fullscreen', 'standard')
            ->setCustomKey('my_custom_key')
            ->addImage( 'hero_image' )
            ->addWysiwyg( 'hero_text' )
            ->addRepeater('cta')
                ->addSelect('link_type')
                ->addChoices('internal', 'external', 'text')
                ->addTrueFalse('cta_animated')
                ->conditional('my_custom_key', '==', '1');


        $expectedConfig = [
            'layouts' => [
                [
                    'name' => 'hero',
                    'sub_fields' => [
                        [
                            'name' => 'hero_type',
                            'key' => 'my_custom_key',
                        ],
                        [
                            'name' => 'hero_image'
                        ],
                        [
                            'name' => 'hero_text'
                        ],
                        [
                            'name' => 'cta',
                            'sub_fields' => [
                                [
                                    'name' => 'link_type'
                                ],
                                [
                                    'key' => 'field_name_hero_cta_cta_animated',
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'my_custom_key',
                                            ]
                                        ]
                                    ]

                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $config = $builder->build();
        $this->assertArraySubset($expectedConfig, $config);

    }

    public function testAllowConditionBasedOnParentField()
    {
        $builder = new \RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FlexibleContentBuilder('name');
        $builder
            ->addLayout('hero')
            ->addSelect('hero_type')
            ->addChoices('fullscreen', 'standard')
            ->addImage( 'hero_image' )
            ->addWysiwyg( 'hero_text' )
            ->addRepeater('cta')
            ->addSelect('link_type')
            ->addChoices('internal', 'external', 'text')
            ->addTrueFalse('cta_animated')
            ->conditional('hero_type', '==', '1');


        $expectedConfig = [
            'layouts' => [
                [
                    'name' => 'hero',
                    'sub_fields' => [
                        [
                            'name' => 'hero_type',
                            'key' => 'field_name_hero_hero_type'
                        ],
                        [
                            'name' => 'hero_image'
                        ],
                        [
                            'name' => 'hero_text'
                        ],
                        [
                            'name' => 'cta',
                            'sub_fields' => [
                                [
                                    'name' => 'link_type'
                                ],
                                [
                                    'key' => 'field_name_hero_cta_cta_animated',
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'field_name_hero_hero_type',
                                            ]
                                        ]
                                    ]

                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $config = $builder->build();
        $this->assertArraySubset($expectedConfig, $config);
    }
}
