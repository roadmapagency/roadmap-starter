<?php

declare(strict_types=1);

namespace RoadmapStarter\Vendor\Doctrine\Inflector\Rules\Portuguese;

use RoadmapStarter\Vendor\Doctrine\Inflector\GenericLanguageInflectorFactory;
use RoadmapStarter\Vendor\Doctrine\Inflector\Rules\Ruleset;

final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset(): Ruleset
    {
        return Rules::getSingularRuleset();
    }

    protected function getPluralRuleset(): Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
