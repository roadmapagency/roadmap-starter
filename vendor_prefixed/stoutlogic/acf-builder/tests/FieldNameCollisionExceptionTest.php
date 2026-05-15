<?php

namespace RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Tests;

use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder;
use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldNameCollisionException;
class FieldNameCollisionExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testClassExists()
    {
        $this->assertTrue(class_exists('RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldNameCollisionException'));
    }
    /**
     * @expectedException \RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldNameCollisionException
     */
    public function testExceptionThrownDuringFieldNameCollision()
    {
        $builder = new FieldsBuilder('Banner');
        $builder->addText('title')->addWysiwyg('content')->addTextarea('content');
    }
    /**
     * @expectedException \RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldNameCollisionException
     */
    public function testExceptionThrownDuringFieldNameCollisionUsingRepeaters()
    {
        $builder = new FieldsBuilder('Banner');
        $builder->addText('title')->addRepeater('slides')->addRadio('slides')->addChoices(1, 2, 3, 4)->endRepeater()->addRadio('slides')->addChoices(1, 2, 3, 4);
    }
    /**
     * @expectedException \RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldNameCollisionException
     */
    public function testExceptionThrownDuringFieldNameCollisionUsingFlexibleContent()
    {
        $builder = new FieldsBuilder('Banner');
        $builder->addText('title')->addFlexibleContent('content')->addLayout('copy')->addWysiwyg('content')->addLayout('gallery')->addRepeater('images')->addImage('image')->endFlexibleContent()->addWysiwyg('content');
    }
    /**
     * @expectedException \RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldNameCollisionException
     */
    public function testExceptionThrownDuringFieldNameCollisionUsingAddFields()
    {
        $builder = new FieldsBuilder('Banner');
        $builder->addText('title')->addWysiwyg('content');
        $builder2 = new FieldsBuilder('Section');
        $builder2->addText('headline')->addWysiwyg('content');
        $builder->addFields($builder2);
    }
}