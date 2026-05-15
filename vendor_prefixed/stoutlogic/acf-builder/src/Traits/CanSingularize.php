<?php

namespace RoadmapStarter\Vendor\StoutLogic\AcfBuilder\Traits;

trait CanSingularize
{
    /**
     * Return a singularized string.
     * @param  string $value
     * @return string
     */
    protected function singularize($value)
    {
        if (class_exists('\RoadmapStarter\Vendor\Doctrine\Inflector\InflectorFactory')) {
            return \RoadmapStarter\Vendor\Doctrine\Inflector\InflectorFactory::create()->build()->singularize($value);
        }

        return \Doctrine\Common\Inflector\Inflector::singularize($value);
    }
}
