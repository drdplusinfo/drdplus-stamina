<?php
namespace DrdPlus\Tests\Stamina;

use DrdPlus\Codes\Properties\PropertyCode;
use DrdPlus\Stamina\PropertyBasedActivity;
use DrdPlus\Stamina\MalusFromFatigue;
use Granam\Tests\Tools\TestWithMockery;

class MalusFromFatigueTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it()
    {
        $malusFromFatigue = MalusFromFatigue::getIt(-2);
        self::assertInstanceOf(MalusFromFatigue::class, $malusFromFatigue);
        self::assertSame(-2, $malusFromFatigue->getValue());

        $malusFromFatigue = MalusFromFatigue::getIt(0);
        self::assertSame(0, $malusFromFatigue->getValue());
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

    /**
     * @test
     */
    public function I_can_get_final_malus_value_according_to_activity()
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
    private function createActivity($usedProperty)
    {
        $activity = $this->mockery(PropertyBasedActivity::class);
        $activity->shouldReceive('usesStrength')
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::STRENGTH === $usedProperty;
            });
        $activity->shouldReceive('usesAgility')
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::AGILITY === $usedProperty;
            });
        $activity->shouldReceive('usesKnack')
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::KNACK === $usedProperty;
            });
        $activity->shouldReceive('usesWill')
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::WILL === $usedProperty;
            });
        $activity->shouldReceive('usesIntelligence')
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::INTELLIGENCE === $usedProperty;
            });
        $activity->shouldReceive('usesCharisma')
            ->andReturnUsing(function () use ($usedProperty) {
                return PropertyCode::CHARISMA === $usedProperty;
            });

        return $activity;
    }
}
