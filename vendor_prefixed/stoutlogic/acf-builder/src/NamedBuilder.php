<?php

namespace RoadmapStarter\Vendor\StoutLogic\AcfBuilder;

/**
 * Builder with a name
 */
interface NamedBuilder extends Builder
{
    /**
     * Returns the name of the builder
     * @return string name
     */
    public function getName();
}
