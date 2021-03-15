<?php declare(strict_types=1);

namespace DrdPlus\Tests\Stamina;

use DrdPlus\Stamina\ReasonToRollAgainstMalusFromFatigue;
use PHPUnit\Framework\TestCase;

class ReasonToRollAgainstMalusFromFatigueTest extends TestCase
{
    /**
     * @test
     */
    public function I_can_use_fatigue_reason(): void
    {
        $fatigueReason = ReasonToRollAgainstMalusFromFatigue::getFatigueReason();
        self::assertInstanceOf(ReasonToRollAgainstMalusFromFatigue::class, $fatigueReason);
        self::assertTrue($fatigueReason->becauseOfFatigue());
        self::assertFalse($fatigueReason->becauseOfRest());
        self::assertSame('fatigue', $fatigueReason->getValue());
        self::assertSame('fatigue', ReasonToRollAgainstMalusFromFatigue::FATIGUE);
        self::assertSame(ReasonToRollAgainstMalusFromFatigue::getIt('fatigue'), $fatigueReason);
    }

    public function I_can_use_rest_reason(): void
    {
        $restReason = ReasonToRollAgainstMalusFromFatigue::getRestReason();
        self::assertInstanceOf(ReasonToRollAgainstMalusFromFatigue::class, $restReason);
        self::assertTrue($restReason->becauseOfRest());
        self::assertFalse($restReason->becauseOfFatigue());
        self::assertSame('rest', $restReason->getValue());
        self::assertSame('rest', ReasonToRollAgainstMalusFromFatigue::REST);
        self::assertSame(ReasonToRollAgainstMalusFromFatigue::getIt('rest'), $restReason);
    }

    /**
     * @test
     */
    public function I_can_not_create_unknown_reason(): void
    {
        $this->expectException(\DrdPlus\Stamina\Exceptions\UnknownReasonToRollAgainstMalus::class);
        $this->expectExceptionMessageMatches('~bored~');
        ReasonToRollAgainstMalusFromFatigue::getEnum('bored');
    }
}
