<?php declare(strict_types=1);

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
     */
    public function I_am_stopped_by_specific_exception_on_invalid_value(): void
    {
        $this->expectException(\Granam\IntegerEnum\Exceptions\WrongValueForIntegerEnum::class);
        $this->expectExceptionMessageMatches('~Drastic teaching~');
        Fatigue::getIt('Drastic teaching');
    }

    /**
     * @test
     */
    public function I_can_not_use_negative_value(): void
    {
        $fatigue = Fatigue::getIt(0);
        self::assertInstanceOf(PositiveInteger::class, $fatigue);
        self::assertSame(0, $fatigue->getValue());
        $this->expectException(\DrdPlus\Stamina\Exceptions\FatigueCanNotBeNegative::class);
        $this->expectExceptionMessageMatches('~-1~');
        Fatigue::getEnum(-1);
    }
}
