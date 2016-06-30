<?php
namespace DrdPlus\Tests\Stamina;

use DrdPlus\Stamina\MalusFromFatigue;

class MalusFromWoundsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function I_can_use_it()
    {
        $malusFromWounds = MalusFromFatigue::getIt(-2);
        self::assertInstanceOf(MalusFromFatigue::class, $malusFromWounds);
        self::assertSame(-2, $malusFromWounds->getValue());

        $malusFromWounds = MalusFromFatigue::getIt(0);
        self::assertSame(0, $malusFromWounds->getValue());
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\UnexpectedMalusValue
     * @expectedExceptionMessageRegExp ~1~
     */
    public function I_can_not_create_positive_malus()
    {
        MalusFromFatigue::getIt(1);
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\UnexpectedMalusValue
     * @expectedExceptionMessageRegExp ~-4~
     */
    public function I_can_not_create_worse_malus_than_minus_three()
    {
        MalusFromFatigue::getIt(-4);
    }
}
