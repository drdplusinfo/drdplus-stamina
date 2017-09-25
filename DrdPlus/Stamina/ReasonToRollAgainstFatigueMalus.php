<?php
declare(strict_types = 1); // on PHP 7+ are standard PHP methods strict to types of given parameters

namespace DrdPlus\Stamina;

use Doctrineum\String\StringEnum;
use Granam\Scalar\ScalarInterface;
use Granam\Tools\ValueDescriber;

class ReasonToRollAgainstFatigueMalus extends StringEnum
{
    const FATIGUE = 'fatigue';

    /**
     * @return ReasonToRollAgainstFatigueMalus|StringEnum
     */
    public static function getFatigueReason(): ReasonToRollAgainstFatigueMalus
    {
        return static::getEnum(self::FATIGUE);
    }

    public function becauseOfFatigue(): bool
    {
        return $this->getValue() === self::FATIGUE;
    }

    const REST = 'rest';

    /**
     * @return ReasonToRollAgainstFatigueMalus|StringEnum
     */
    public static function getRestReason(): ReasonToRollAgainstFatigueMalus
    {
        return static::getEnum(self::REST);
    }

    public function becauseOfRest(): bool
    {
        return $this->getValue() === self::REST;
    }

    /**
     * @param string $reasonCode
     * @return ReasonToRollAgainstFatigueMalus|StringEnum
     * @throws \DrdPlus\Stamina\Exceptions\UnknownReasonToRollAgainstMalus
     */
    public static function getIt($reasonCode): ReasonToRollAgainstFatigueMalus
    {
        return static::getEnum($reasonCode);
    }

    /**
     * @param bool|float|int|ScalarInterface|string $enumValue
     * @return string
     * @throws \DrdPlus\Stamina\Exceptions\UnknownReasonToRollAgainstMalus
     * @throws \Doctrineum\String\Exceptions\UnexpectedValueToEnum
     */
    protected static function convertToEnumFinalValue($enumValue): string
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