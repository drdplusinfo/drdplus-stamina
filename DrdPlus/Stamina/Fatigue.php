<?php
namespace DrdPlus\Stamina;

use Doctrineum\Integer\IntegerEnum;
use Granam\Tools\ValueDescriber;

/**
 * @method static Fatigue getEnum($value)
 */
class Fatigue extends IntegerEnum
{
    /**
     * @param int $pointsOfFatigue
     * @return Fatigue
     * @throws \DrdPlus\Stamina\Exceptions\FatigueCanNotBeNegative
     * @throws \Doctrineum\Integer\Exceptions\UnexpectedValueToConvert
     */
    public static function getIt($pointsOfFatigue)
    {
        return static::getEnum($pointsOfFatigue);
    }

    /**
     * @param mixed $enumValue
     * @return int
     * @throws \DrdPlus\Stamina\Exceptions\FatigueCanNotBeNegative
     * @throws \Doctrineum\Integer\Exceptions\UnexpectedValueToConvert
     */
    protected static function convertToEnumFinalValue($enumValue)
    {
        $finalValue = parent::convertToEnumFinalValue($enumValue);
        if ($finalValue < 0) {
            throw new Exceptions\FatigueCanNotBeNegative(
                'Expected at least zero, got ' . ValueDescriber::describe($enumValue)
            );
        }

        return $finalValue;
    }

}