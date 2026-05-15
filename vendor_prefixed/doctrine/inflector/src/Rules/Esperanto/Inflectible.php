<?php

declare(strict_types=1);

namespace RoadmapStarter\Vendor\Doctrine\Inflector\Rules\Esperanto;

use RoadmapStarter\Vendor\Doctrine\Inflector\Rules\Pattern;
use RoadmapStarter\Vendor\Doctrine\Inflector\Rules\Substitution;
use RoadmapStarter\Vendor\Doctrine\Inflector\Rules\Transformation;
use RoadmapStarter\Vendor\Doctrine\Inflector\Rules\Word;

class Inflectible
{
    /** @return Transformation[] */
    public static function getSingular(): iterable
    {
        yield new Transformation(new Pattern('oj$'), 'o');
    }

    /** @return Transformation[] */
    public static function getPlural(): iterable
    {
        yield new Transformation(new Pattern('o$'), 'oj');
    }

    /** @return Substitution[] */
    public static function getIrregular(): iterable
    {
        yield new Substitution(new Word(''), new Word(''));
    }
}
