<?php

namespace RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Tests\Transform;

use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder;
use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\ConditionalBuilder;
use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Transform;
use Prophecy\Argument;

class FlexibleContentLayoutTest extends \PHPUnit_Framework_TestCase
{
    public function testTransformValue()
    {
        $builder = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder');
        $builder
            ->getName()
            ->willReturn('Fields Builder Name');

        $transform = new Transform\FlexibleContentLayout($builder->reveal());

        $expected = [
            'sub_fields' => 'fields',
            'label' => 'title',
        ];

        $actual = $transform->transform([
            'fields' => 'fields',
            'title' => 'title',
        ]);

        $this->assertSame($expected, $actual);
    }
}
