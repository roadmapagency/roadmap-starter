<?php

namespace RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Tests;

use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Builder;

class ParentDelegationBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testReturningParent()
    {
        $parent = $this
            ->getMockBuilder('RoadmapStarter\Vendor\StoutLogic\AcfBuilder\ParentDelegationBuilder')
            ->setMethods(['parentMethod', 'build'])
            ->getMockForAbstractClass();
        $child = $this->getMockForAbstractClass('RoadmapStarter\Vendor\StoutLogic\AcfBuilder\ParentDelegationBuilder');
        $child->setParentContext($parent);

        $parent->expects($this->once())->method('parentMethod');
        $child->parentMethod();
    }

    public function testThrowingException()
    {
        $parent = $this->getMockForAbstractClass('RoadmapStarter\Vendor\StoutLogic\AcfBuilder\ParentDelegationBuilder');
        $child = $this->getMockForAbstractClass('RoadmapStarter\Vendor\StoutLogic\AcfBuilder\ParentDelegationBuilder');
        $child->setParentContext($parent);

        $this->setExpectedException('\Exception');
        $child->nonExistantParentMethod();
    }
}
