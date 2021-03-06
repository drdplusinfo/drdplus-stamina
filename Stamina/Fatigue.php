<?php declare(strict_types=1);

namespace DrdPlus\Stamina;

use Granam\Integer\PositiveInteger;
use Granam\IntegerEnum\IntegerEnum;
use Granam\Tools\ValueDescriber;

class Fatigue extends IntegerEnum implements PositiveInteger
{
    /**
     * @param int $pointsOfFatigue
     * @return Fatigue|IntegerEnum
     * @throws \DrdPlus\Stamina\Exceptions\FatigueCanNotBeNegative
     * @throws \Granam\IntegerEnum\Exceptions\WrongValueForIntegerEnum
     */
    public static function getIt($pointsOfFatigue): Fatigue
    {
        return static::getEnum($pointsOfFatigue);
    }

    /**
     * @param mixed $enumValue
     * @return int
     * @throws \DrdPlus\Stamina\Exceptions\FatigueCanNotBeNegative
     * @throws \Granam\IntegerEnum\Exceptions\WrongValueForIntegerEnum
     */
    protected static function convertToEnumFinalValue($enumValue): int
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
