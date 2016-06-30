<?php
namespace DrdPlus\Tests\Stamina;

use DrdPlus\Stamina\FatigueSize;
use Granam\Integer\IntegerInterface;

class FatigueSizeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function I_can_use_it_as_an_integer()
    {
        $woundSize = new FatigueSize(123);
        self::assertInstanceOf(IntegerInterface::class, $woundSize);
        self::assertSame(123, $woundSize->getValue());
        $woundSizeByFactory = FatigueSize::createIt(123);
        self::assertEquals($woundSize, $woundSizeByFactory);
        self::assertNotSame($woundSize, $woundSizeByFactory);
    }

    /**
     * @test
     * @expectedException \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @expectedExceptionMessageRegExp ~Terribly wounded by horrible pebble~
     */
    public function I_am_stopped_by_specific_exception_on_invalid_value()
    {
        new FatigueSize('Terribly wounded by horrible pebble');
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\FatigueSizeCanNotBeNegative
     * @expectedExceptionMessageRegExp ~-1~
     */
    public function I_can_not_use_negative_value()
    {
        new FatigueSize(-1);
    }
}
