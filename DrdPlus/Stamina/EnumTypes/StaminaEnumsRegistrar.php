<?php
namespace DrdPlus\Stamina\EnumTypes;

use Doctrineum\DateInterval\DBAL\Types\DateIntervalType;

class StaminaEnumsRegistrar
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function registerAll(): void
    {
        DateIntervalType::registerSelf();

        // Stamina
        MalusFromFatigueType::registerSelf();
        ReasonToRollAgainstFatigueMalusType::registerSelf();
        FatigueType::registerSelf();
    }
}