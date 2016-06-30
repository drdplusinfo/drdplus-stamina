<?php
namespace DrdPlus\Stamina;

use Granam\Integer\IntegerObject;
use Granam\Tools\ValueDescriber;

class FatigueSize extends IntegerObject
{
    /**
     * @param $value
     * @return FatigueSize
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \DrdPlus\Stamina\Exceptions\FatigueSizeCanNotBeNegative
     */
    public static function createIt($value)
    {
        return new static($value);
    }

    /**
     * @param mixed $value
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \DrdPlus\Stamina\Exceptions\FatigueSizeCanNotBeNegative
     */
    public function __construct($value)
    {
        parent::__construct($value);

        if ($this->getValue() < 0) {
            throw new Exceptions\FatigueSizeCanNotBeNegative(
                'Expected at least zero, got ' . ValueDescriber::describe($value)
            );
        }
    }
}