<?php declare(strict_types=1);

namespace DrdPlus\Tests\Stamina;

use DrdPlus\Codes\Properties\PropertyCode;
use DrdPlus\Stamina\PropertyBasedActivity;
use DrdPlus\Stamina\MalusFromFatigue;
use Granam\TestWithMockery\TestWithMockery;

class MalusFromFatigueTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it(): void
    {
        $malusFromFatigue = MalusFromFatigue::getIt(-2);
        self::assertInstanceOf(MalusFromFatigue::class, $malusFromFatigue);
        self::assertSame(-2, $malusFromFatigue->getValue());

        $malusFromFatigue = MalusFromFatigue::getIt(0);
        self::assertSame(0, $malusFromFatigue->getValue());
    }

    /**
     * @test
     */
    public function I_can_not_create_positive_malus(): void
    {
        $this->expectException(\DrdPlus\Stamina\Exceptions\UnexpectedMalusValue::class);
        $this->expectExceptionMessageMatches('~1~');
        MalusFromFatigue::getIt(1);
    }

    /**
     * @test
     */
    public function I_can_not_create_worse_malus_than_minus_three(): void
    {
        $this->expectException(\DrdPlus\Stamina\Exceptions\UnexpectedMalusValue::class);
        $this->expectExceptionMessageMatches('~-4~');
        MalusFromFatigue::getIt(-4);
    }

    /**
     * @test
     */
    public function I_can_get_final_malus_value_according_to_activity(): void
    {
        $evenMalus = MalusFromFatigue::getIt(-3);
        $oddMalus = MalusFromFatigue::getIt(-2);

        foreach ([PropertyCode::STRENGTH, PropertyCode::AGILITY, PropertyCode::KNACK] as $hardProperty) {
            self::assertSame(-3, $evenMalus->getValueForActivity($this->createActivity($hardProperty)));
            self::assertSame(-2, $oddMalus->getValueForActivity($this->createActivity($hardProperty)));
        }
        foreach ([PropertyCode::WILL, PropertyCode::INTELLIGENCE, PropertyCode::CHARISMA] as $softProperty) {
            self::assertSame(-2, $evenMalus->getValueForActivity($this->createActivity($softProperty)));
            self::assertSame(-1, $oddMalus->getValueForActivity($this->createActivity($softProperty)));
        }
    }

    /**
     * @param string $usedProperty
     * @return \Mockery\MockInterface|PropertyBasedActivity
     */
    private function createActivity(string $usedProperty)
    {
        $activity = $this->mockery(PropertyBasedActivity::class);
        $activity->shouldReceive('usesStrength')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::STRENGTH === $usedProperty;
            });
        $activity->shouldReceive('usesAgility')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::AGILITY === $usedProperty;
            });
        $activity->shouldReceive('usesKnack')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::KNACK === $usedProperty;
            });
        $activity->shouldReceive('usesWill')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::WILL === $usedProperty;
            });
        $activity->shouldReceive('usesIntelligence')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::INTELLIGENCE === $usedProperty;
            });
        $activity->shouldReceive('usesCharisma')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::CHARISMA === $usedProperty;
            });

        return $activity;
    }
}
