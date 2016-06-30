<?php
namespace DrdPlus\Stamina\EnumTypes;

use Doctrineum\Integer\IntegerEnumType;

class FatigueType extends IntegerEnumType
{
    const FATIGUE = 'fatigue';

    /**
     * @return string
     */
    public function getName()
    {
        return self::FATIGUE;
    }
}