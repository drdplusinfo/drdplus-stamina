<?php
namespace DrdPlus\Stamina\EnumTypes;

use Doctrineum\DateInterval\DBAL\Types\DateIntervalType;

class StaminaEnumsRegistrar
{
    public static function registerAll()
    {
        DateIntervalType::registerSelf();

        // Stamina
        MalusFromFatigueType::registerSelf();
        ReasonToRollAgainstFatigueMalusType::registerSelf();
    }
}