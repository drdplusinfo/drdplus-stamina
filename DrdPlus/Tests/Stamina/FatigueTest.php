<?php
namespace DrdPlus\Tests\Stamina;

use DrdPlus\Stamina\Fatigue;
use Granam\Integer\IntegerInterface;

class FatigueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function I_can_use_it_as_an_integer()
    {
        $fatigue = Fatigue::getIt(123);
        self::assertInstanceOf(IntegerInterface::class, $fatigue);
        self::assertSame(123, $fatigue->getValue());
    }

    /**
     * @test
     * @expectedException \Doctrineum\Integer\Exceptions\UnexpectedValueToConvert
     * @expectedExceptionMessageRegExp ~Drastic teaching~
     */
    public function I_am_stopped_by_specific_exception_on_invalid_value()
    {
        Fatigue::getIt('Drastic teaching');
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\FatigueCanNotBeNegative
     * @expectedExceptionMessageRegExp ~-1~
     */
    public function I_can_not_use_negative_value()
    {
        Fatigue::getEnum(-1);
    }
}