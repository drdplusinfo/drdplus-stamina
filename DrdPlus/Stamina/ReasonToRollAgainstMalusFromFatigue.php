<?php
declare(strict_types=1);

namespace DrdPlus\Stamina;

use Granam\Scalar\ScalarInterface;
use Granam\String\StringInterface;
use Granam\StringEnum\StringEnum;
use Granam\Tools\ValueDescriber;

class ReasonToRollAgainstMalusFromFatigue extends StringEnum
{
    public const FATIGUE = 'fatigue';
    public const REST = 'rest';

    /**
     * @return ReasonToRollAgainstMalusFromFatigue|StringEnum
     */
    public static function getFatigueReason(): ReasonToRollAgainstMalusFromFatigue
    {
        return static::getEnum(self::FATIGUE);
    }

    public function becauseOfFatigue(): bool
    {
        return $this->getValue() === self::FATIGUE;
    }

    /**
     * @return ReasonToRollAgainstMalusFromFatigue|StringEnum
     */
    public static function getRestReason(): ReasonToRollAgainstMalusFromFatigue
    {
        return static::getEnum(self::REST);
    }

    public function becauseOfRest(): bool
    {
        return $this->getValue() === self::REST;
    }

    /**
     * @param string|StringInterface $reasonCode
     * @return ReasonToRollAgainstMalusFromFatigue|StringEnum
     * @throws \DrdPlus\Stamina\Exceptions\UnknownReasonToRollAgainstMalus
     */
    public static function getIt($reasonCode): ReasonToRollAgainstMalusFromFatigue
    {
        return static::getEnum($reasonCode);
    }

    /**
     * @param bool|float|int|ScalarInterface|string $enumValue
     * @return string
     * @throws \DrdPlus\Stamina\Exceptions\UnknownReasonToRollAgainstMalus
     * @throws \Granam\StringEnum\Exceptions\WrongValueForStringEnum
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