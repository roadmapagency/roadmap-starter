<?php

namespace RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Tests\Transform;

use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder;
use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Transform;

class ConditionalFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testIsRecursive()
    {
        $builder = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder');
        $transform = new Transform\ConditionalField($builder->reveal());
        $this->assertInstanceOf('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Transform\RecursiveTransform', $transform);
    }

    public function testGetKeys()
    {
        $builder = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder');
        $transform = new Transform\ConditionalField($builder->reveal());
        $this->assertSame(['field'], $transform->getKeys());
    }

    public function testTransformValue()
    {
        $field = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldBuilder');
        $field
            ->getKey()
            ->willReturn('field_key');

        $builder = $this->prophesize('\RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder');
        $builder
            ->getField('value')
            ->willReturn($field->reveal());

        $builder
            ->fieldExists('value')
            ->willReturn(true);

        $transform = new Transform\ConditionalField($builder->reveal());
        $this->assertSame('field_key', $transform->transformValue('value'));
    }
}
