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
        $woundSize = new Fatigue(123);
        self::assertInstanceOf(IntegerInterface::class, $woundSize);
        self::assertSame(123, $woundSize->getValue());
        $woundSizeByFactory = Fatigue::getIt(123);
        self::assertEquals($woundSize, $woundSizeByFactory);
        self::assertNotSame($woundSize, $woundSizeByFactory);
    }

    /**
     * @test
     * @expectedException \Doctrineum\Integer\Exceptions\UnexpectedValueToConvert
     * @expectedExceptionMessageRegExp ~Drastic teaching~
     */
    public function I_am_stopped_by_specific_exception_on_invalid_value()
    {
        new Fatigue('Drastic teaching');
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\FatigueCanNotBeNegative
     * @expectedExceptionMessageRegExp ~-1~
     */
    public function I_can_not_use_negative_value()
    {
        new Fatigue(-1);
    }
}
