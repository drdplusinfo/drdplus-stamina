<?php
namespace DrdPlus\Stamina\EnumTypes;

use Doctrineum\String\StringEnumType;

class ReasonToRollAgainstFatigueMalusType extends StringEnumType
{
    public const REASON_TO_ROLL_AGAINST_FATIGUE_MALUS = 'reason_to_roll_against_fatigue_malus';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::REASON_TO_ROLL_AGAINST_FATIGUE_MALUS;
    }
}