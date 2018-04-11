<?php
namespace DrdPlus\Stamina\EnumTypes;

use Doctrineum\Integer\IntegerEnumType;

class FatigueType extends IntegerEnumType
{
    public const FATIGUE = 'fatigue';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::FATIGUE;
    }
}