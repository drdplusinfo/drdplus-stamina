<?php
declare(strict_types = 1);

namespace DrdPlus\Stamina\EnumTypes;

use Doctrineum\Integer\IntegerEnumType;

class MalusFromFatigueType extends IntegerEnumType
{
    public const MALUS_FROM_FATIGUE = 'malus_from_fatigue';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::MALUS_FROM_FATIGUE;
    }
}