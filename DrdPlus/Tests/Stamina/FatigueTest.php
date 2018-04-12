<?php
namespace DrdPlus\Tests\Stamina;

use DrdPlus\Stamina\Fatigue;
use Granam\Integer\IntegerInterface;
use Granam\Integer\PositiveInteger;
use PHPUnit\Framework\TestCase;

class FatigueTest extends TestCase
{
    /**
     * @test
     */
    public function I_can_use_it_as_an_integer(): void
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
    public function I_am_stopped_by_specific_exception_on_invalid_value(): void
    {
        Fatigue::getIt('Drastic teaching');
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\FatigueCanNotBeNegative
     * @expectedExceptionMessageRegExp ~-1~
     */
    public function I_can_not_use_negative_value(): void
    {
        try {
            $fatigue = Fatigue::getIt(0);
            self::assertInstanceOf(PositiveInteger::class, $fatigue);
            self::assertSame(0, $fatigue->getValue());
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getMessage());
        }
        Fatigue::getEnum(-1);
    }
}