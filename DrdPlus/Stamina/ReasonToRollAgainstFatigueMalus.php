<?php
namespace DrdPlus\Stamina;

use Doctrineum\String\StringEnum;
use Granam\Scalar\ScalarInterface;
use Granam\Tools\ValueDescriber;

class ReasonToRollAgainstFatigueMalus extends StringEnum
{
    const FATIGUE = 'fatigue';

    /**
     * @return ReasonToRollAgainstFatigueMalus
     */
    public static function getFatigueReason()
    {
        return static::getEnum(self::FATIGUE);
    }

    public function becauseOfFatigue()
    {
        return $this->getValue() === self::FATIGUE;
    }

    const REST = 'rest';

    /**
     * @return ReasonToRollAgainstFatigueMalus
     */
    public static function getRestReason()
    {
        return static::getEnum(self::REST);
    }

    public function becauseOfRest()
    {
        return $this->getValue() === self::REST;
    }

    /**
     * @param string $reasonCode
     * @return ReasonToRollAgainstFatigueMalus
     * @throws \DrdPlus\Stamina\Exceptions\UnknownReasonToRollAgainstMalus
     */
    public static function getIt($reasonCode)
    {
        return static::getEnum($reasonCode);
    }

    /**
     * @param bool|float|int|ScalarInterface|string $enumValue
     * @return string
     * @throws \DrdPlus\Stamina\Exceptions\UnknownReasonToRollAgainstMalus
     */
    protected static function convertToEnumFinalValue($enumValue)
    {
        $finalValue = parent::convertToEnumFinalValue($enumValue);
        if ($finalValue !== self::FATIGUE && $finalValue !== self::REST) {
            throw new Exceptions\UnknownReasonToRollAgainstMalus(
                'Expected one of ' . self::FATIGUE . ' or ' . self::REST . ', got ' . ValueDescriber::describe($enumValue)
            );
        }

        return $finalValue;
    }

}