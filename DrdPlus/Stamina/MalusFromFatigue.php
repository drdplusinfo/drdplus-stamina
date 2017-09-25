<?php
namespace DrdPlus\Stamina;

use Doctrineum\Integer\IntegerEnum;
use DrdPlus\Calculations\SumAndRound;
use Granam\Tools\ValueDescriber;

class MalusFromFatigue extends IntegerEnum
{
    /**
     * @param int $malusValue
     * @return MalusFromFatigue|IntegerEnum
     * @throws \DrdPlus\Stamina\Exceptions\UnexpectedMalusValue
     * @throws \Doctrineum\Integer\Exceptions\UnexpectedValueToConvert
     */
    public static function getIt($malusValue): MalusFromFatigue
    {
        return static::getEnum($malusValue);
    }

    const MOST = -3;

    /**
     * @param mixed $enumValue
     * @return int
     * @throws \DrdPlus\Stamina\Exceptions\UnexpectedMalusValue
     * @throws \Doctrineum\Integer\Exceptions\UnexpectedValueToConvert
     */
    protected static function convertToEnumFinalValue($enumValue): int
    {
        $finalValue = parent::convertToEnumFinalValue($enumValue);
        if ($finalValue > 0 // note: comparing negative numbers
            || $finalValue < self::MOST
        ) {
            throw new Exceptions\UnexpectedMalusValue(
                'Malus can be between 0 and ' . self::MOST . ', got ' . ValueDescriber::describe($enumValue)
            );
        }

        return $finalValue;
    }

    /**
     * @param PropertyBasedActivity $activity
     * @return int
     */
    public function getValueForActivity(PropertyBasedActivity $activity): int
    {
        if ($activity->usesStrength() || $activity->usesAgility() || $activity->usesKnack()) {
            return $this->getValue();
        }

        return SumAndRound::half($this->getValue());
    }

}