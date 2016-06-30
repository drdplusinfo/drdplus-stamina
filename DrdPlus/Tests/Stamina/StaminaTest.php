<?php
namespace DrdPlus\Tests\Stamina;

use Drd\DiceRoll\Templates\Rollers\Roller2d6DrdPlus;
use Drd\DiceRoll\Templates\Rollers\SpecificRolls\Roll2d6DrdPlus;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Stamina\Fatigue;
use DrdPlus\Stamina\GridOfFatigue;
use DrdPlus\Stamina\MalusFromFatigue;
use DrdPlus\Stamina\RestPower;
use DrdPlus\Stamina\Stamina;
use DrdPlus\Stamina\ReasonToRollAgainstFatigueMalus;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Tables\Measurements\Wounds\WoundsTable;
use Granam\Tests\Tools\TestWithMockery;

/** @noinspection LongInheritanceChainInspection */
class StaminaTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it()
    {
        $stamina = $this->createStaminaToTest(123);

        self::assertSame(123, $stamina->getFatigueBoundaryValue());
        self::assertSame(369, $stamina->getRemainingStaminaAmount());
        self::assertSame(369, $stamina->getStaminaMaximum());
        self::assertInstanceOf(Fatigue::class, $fatigue = $stamina->getFatigue());
        self::assertSame(0, $fatigue->getValue());
        self::assertInstanceOf(MalusFromFatigue::class, $malusFromFatigue = $stamina->getMalusFromFatigue());
        self::assertSame(0, $malusFromFatigue->getValue());
    }

    /**
     * @param int $fatigueBoundaryValue
     * @return Stamina
     */
    private function createStaminaToTest($fatigueBoundaryValue)
    {
        $stamina = new Stamina($fatigueBoundary = $this->createFatigueBoundary($fatigueBoundaryValue));
        $this->assertRested($stamina, $fatigueBoundary);

        return $stamina;
    }

    /**
     * @param $value
     * @return \Mockery\MockInterface|FatigueBoundary
     */
    private function createFatigueBoundary($value)
    {
        $fatigue = $this->mockery(FatigueBoundary::class);
        $fatigue->shouldReceive('getValue')
            ->andReturn($value);

        return $fatigue;
    }

    private function assertRested(Stamina $stamina, FatigueBoundary $fatigueBoundary)
    {
        self::assertNull($stamina->getId(), 'Not yet persisted stamina should not has filled ID (it is database responsibility in this case)');
        self::assertSame($fatigueBoundary->getValue(), $stamina->getFatigueBoundaryValue());
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum(), $stamina->getFatigueBoundaryValue());
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum() * 3, $stamina->getStaminaMaximum());
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum() * 3, $stamina->getRemainingStaminaAmount());
        self::assertTrue($stamina->isAlive());
        self::assertTrue($stamina->isConscious());
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());
        self::assertInstanceOf(GridOfFatigue::class, $stamina->getGridOfFatigue());
    }

    /**
     * @test
     * @dataProvider provideConsciousAndAlive
     * @param int $fatigueBoundary
     * @param int $fatigue
     * @param bool $isConscious
     * @param bool $isAlive
     */
    public function I_can_easily_find_out_if_creature_is_conscious_and_alive($fatigueBoundary, $fatigue, $isConscious, $isAlive)
    {
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue($fatigue));

        self::assertSame($isConscious, $stamina->isConscious());
        self::assertSame($isAlive, $stamina->isAlive());
    }

    public function provideConsciousAndAlive()
    {
        return [
            [1, 0, true, true], // fresh
            [1, 1, true, true], // tired
            [1, 2, false, true], // unconscious
            [1, 3, false, false], // dead
        ];
    }

    /**
     * @param int $value
     * @return \Mockery\MockInterface|Fatigue
     */
    private function createFatigue($value)
    {
        $fatigueSize = $this->mockery(Fatigue::class);
        $fatigueSize->shouldReceive('getValue')
            ->andReturn($value);

        return $fatigueSize;
    }

    // ROLL ON MALUS RESULT

    /**
     * @test
     * @dataProvider provideDecreasingRollAgainstMalusData
     * @param $willValue
     * @param $rollValue
     * @param $expectedMalus
     */
    public function I_should_roll_against_malus_from_fatigue_because_of_new_fatigue($willValue, $rollValue, $expectedMalus)
    {
        $stamina = $this->createStaminaToTest(10);
        $stamina->addFatigue($this->createFatigue(10));
        self::assertTrue($stamina->needsToRollAgainstMalus());
        self::assertSame(ReasonToRollAgainstFatigueMalus::getFatigueReason(), $stamina->getReasonToRollAgainstFatigueMalus());
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue(
                $this->createWill($willValue),
                $this->createRoller2d6Plus($rollValue)
            )->getValue()
        );
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());
    }

    public function provideDecreasingRollAgainstMalusData()
    {
        return [
            [7, 8, 0],
            [99, 99, 0],
            [6, 4, -1],
            [6, 8, -1],
            [3, 2, -2],
            [2, 3, -2],
            [1, 1, -3],
        ];
    }

    /**
     * @param int $value
     * @return \Mockery\MockInterface|Will
     */
    private function createWill($value = null)
    {
        $will = $this->mockery(Will::class);
        if ($value !== null) {
            $will->shouldReceive('getValue')
                ->andReturn($value);
        }

        return $will;
    }

    /**
     * @param $value
     * @return \Mockery\MockInterface|Roller2d6DrdPlus
     */
    private function createRoller2d6Plus($value = null)
    {
        $roller = $this->mockery(Roller2d6DrdPlus::class);
        if ($value !== null) {
            $roller->shouldReceive('roll')
                ->andReturn($roll = $this->mockery(Roll2d6DrdPlus::class));
            $roll->shouldReceive('getValue')
                ->andReturn($value);
            $roll->shouldReceive('getRolledNumbers')
                ->andReturn([$value]);
        }

        return $roller;
    }

    /**
     * @test
     * @dataProvider provideIncreasingRollAgainstMalusData
     * @param $willValue
     * @param $rollValue
     * @param $expectedMalus
     */
    public function I_should_roll_against_malus_from_fatigue_because_of_rest($willValue, $rollValue, $expectedMalus)
    {
        $stamina = $this->createStaminaToTest(10);
        $stamina->addFatigue($this->createFatigue(4));
        $stamina->addFatigue($this->createFatigue(4));
        $stamina->addFatigue($this->createFatigue(4));
        $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoller2d6Plus(3)); // -3 malus as a result
        self::assertFalse($stamina->needsToRollAgainstMalus());
        $stamina->rest($this->createRestPower(1));
        self::assertSame(ReasonToRollAgainstFatigueMalus::getRestReason(), $stamina->getReasonToRollAgainstFatigueMalus());
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue(
                $this->createWill($willValue),
                $this->createRoller2d6Plus($rollValue)
            )->getValue()
        );
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());
    }

    public function provideIncreasingRollAgainstMalusData()
    {
        return [
            [1, 1, -3],
            [3, 2, -2],
            [2, 3, -2],
            [6, 4, -1],
            [6, 8, -1],
            [7, 8, 0],
            [99, 99, 0],
        ];
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\UselessRollAgainstMalus
     */
    public function I_can_not_roll_on_malus_from_fatigue_if_not_needed()
    {
        $stamina = $this->createStaminaToTest(10);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(), $this->createRoller2d6Plus());
    }

    // ROLL ON MALUS EXPECTED

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function I_can_not_add_new_fatigue_if_roll_on_malus_expected()
    {
        $stamina = $this->createStaminaToTest(10);
        try {
            $stamina->addFatigue($this->createFatigue(10));
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getTraceAsString());
        }
        $stamina->addFatigue($this->createFatigue(10));
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function I_can_not_rest_if_roll_on_malus_expected()
    {
        $stamina = $this->createStaminaToTest(10);
        try {
            $stamina->addFatigue($this->createFatigue(4));
            $stamina->addFatigue($this->createFatigue(4));
            $stamina->addFatigue($this->createFatigue(4));
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getTraceAsString());
        }
        $stamina->rest($this->createRestPower(5));
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function I_can_not_get_malus_from_fatigue_if_roll_on_it_expected()
    {
        $stamina = $this->createStaminaToTest(10);
        try {
            $stamina->addFatigue($this->createFatigue(14));
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getTraceAsString());
        }
        $stamina->getMalusFromFatigue();
    }

    // MALUS CONDITIONAL CHANGES

    /**
     * @test
     * @dataProvider provideRollForMalus
     * @param $willValue
     * @param $rollValue
     * @param $expectedMalus
     */
    public function Malus_can_increase_on_new_fatigue($willValue, $rollValue, $expectedMalus)
    {
        $stamina = $this->createStaminaToTest(5);

        $stamina->addFatigue($this->createFatigue(5));
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue($this->createWill($willValue), $this->createRoller2d6Plus($rollValue))->getValue()
        );
        self::assertSame($expectedMalus, $stamina->getMalusFromFatigue()->getValue());

        for ($currentWillValue = $willValue, $currentRollValue = $rollValue;
            $currentRollValue > -2 && $currentWillValue > -2;
            $currentRollValue--, $currentWillValue--
        ) {
            $stamina->addFatigue($this->createFatigue(3));
            $currentlyExpectedMalus = max(0, min(3, (int)floor(($currentWillValue + $currentRollValue) / 5))) - 3; // 0; -1; -2; -3
            self::assertSame(
                $currentlyExpectedMalus, // malus can increase (be more negative)
                $stamina->rollAgainstMalusFromFatigue($this->createWill($currentWillValue), $this->createRoller2d6Plus($currentRollValue))->getValue(),
                "For will $currentWillValue and roll $currentRollValue has been expected malus $currentlyExpectedMalus"
            );
            self::assertSame($currentlyExpectedMalus, $stamina->getMalusFromFatigue()->getValue());
            $stamina->rest($this->createRestPower(3)); // "resetting" currently given fatigue
            // low values to ensure untouched malus (should not be increased, therefore changed here at all, on rest)
            $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoller2d6Plus(-1));
        }
    }

    public function provideRollForMalus()
    {
        return [
            [1, 1, -3],
            [-5, -5, -3],
            [10, 5, 0],
            [15, 0, 0],
            [13, 1, -1],
            [2, 7, -2],
            [3, 7, -1],
            [3, 1, -3],
            [3, 2, -2],
        ];
    }

    /**
     * @test
     * @dataProvider provideRollForMalus
     * @param $willValue
     * @param $rollValue
     * @param $expectedMalus
     */
    public function Malus_can_not_decrease_on_new_fatigue($willValue, $rollValue, $expectedMalus)
    {
        $stamina = $this->createStaminaToTest(5);

        $stamina->addFatigue($this->createFatigue(5));
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue($this->createWill($willValue), $this->createRoller2d6Plus($rollValue))->getValue()
        );
        self::assertSame($expectedMalus, $stamina->getMalusFromFatigue()->getValue());

        for ($currentWillValue = $willValue, $currentRollValue = $rollValue;
            $currentRollValue < 16 && $currentWillValue < 10;
            $currentRollValue++, $currentWillValue++
        ) {
            $stamina->addFatigue($this->createFatigue(3));
            self::assertSame(
                $expectedMalus, // malus should not be decreased (be closer to zero)
                $stamina->rollAgainstMalusFromFatigue($this->createWill($currentWillValue), $this->createRoller2d6Plus($currentRollValue))->getValue(),
                "Even for will $currentWillValue and roll $currentRollValue has been expected previous malus $expectedMalus"
            );
            self::assertSame($expectedMalus, $stamina->getMalusFromFatigue()->getValue());
            $stamina->rest($this->createRestPower(3)); // "resetting" currently given fatigue
            // low values to ensure untouched malus (should not be increased, therefore changed here at all, on rest)
            $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoller2d6Plus(-1));
        }
    }

    /**
     * @test
     */
    public function Malus_is_not_increased_on_new_rest_by_worse_roll()
    {
        $stamina = $this->createStaminaToTest(5);
        self::assertSame(0, $stamina->getMalusFromFatigue()->getValue());

        // 3x fatigue to reach some malus
        $stamina->addFatigue($this->createFatigue(2));
        $stamina->addFatigue($this->createFatigue(2));
        $stamina->addFatigue($this->createFatigue(2));
        $stamina->rollAgainstMalusFromFatigue($this->createWill(0), $this->createRoller2d6Plus(11));
        self::assertSame(-1, $stamina->getMalusFromFatigue()->getValue());
        self::assertSame(1, $stamina->rest($this->createRestPower(1)));
        $stamina->rollAgainstMalusFromFatigue($this->createWill(0), $this->createRoller2d6Plus(-2)); // much worse roll
        self::assertSame(-1, $stamina->getMalusFromFatigue()->getValue(), 'Malus should not be increased');
    }

    /**
     * @test
     */
    public function I_can_be_fatigued()
    {
        $stamina = $this->createStaminaToTest(5);

        self::assertSame(2, $stamina->addFatigue($this->createFatigue(2))->getValue());
        self::assertSame(2, $stamina->getFatigue()->getValue());
        self::assertSame(13, $stamina->getRemainingStaminaAmount());
        self::assertSame(0, $stamina->getMalusFromFatigue()->getValue());
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());

        self::assertSame(3, $stamina->addFatigue($this->createFatigue(1))->getValue());
        self::assertSame(3, $stamina->getFatigue()->getValue());
        self::assertSame(12, $stamina->getRemainingStaminaAmount());
        self::assertSame(0, $stamina->getMalusFromFatigue()->getValue());
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());
    }

    /**
     * @test
     */
    public function I_can_rest()
    {
        $stamina = $this->createStaminaToTest(7);
        self::assertSame(21, $stamina->getRemainingStaminaAmount());

        $stamina->addFatigue($this->createFatigue(1));
        $stamina->addFatigue($this->createFatigue(3));
        $stamina->addFatigue($this->createFatigue(2));

        self::assertSame(15, $stamina->getRemainingStaminaAmount());
        self::assertSame(
            5 /* rest power of value 4 rests up to 5 points of fatigue, see WoundsTable and related bonus-to-value conversion */,
            $stamina->rest(new RestPower(4, new WoundsTable()))
        );
        self::assertSame(20, $stamina->getRemainingStaminaAmount());
        self::assertSame(1, $stamina->getFatigue()->getValue());
        self::assertSame(0, $stamina->getMalusFromFatigue()->getValue());
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());

        self::assertSame(1, $stamina->rest($this->createRestPower(10)));
        self::assertSame(21, $stamina->getRemainingStaminaAmount());
        self::assertSame(0, $stamina->getFatigue()->getValue());
    }

    /**
     * @test
     */
    public function I_can_rest_so_little_so_nothing_changes()
    {
        $stamina = $this->createStaminaToTest(4);

        $stamina->addFatigue($this->createFatigue(7));
        $stamina->rollAgainstMalusFromFatigue($this->createWill(-2), $this->createRoller2d6Plus(5));
        self::assertSame(-3, $stamina->getMalusFromFatigue()->getValue());

        self::assertSame(0, $stamina->rest($this->createRestPower(0)));
        self::assertSame(-3, $stamina->getMalusFromFatigue()->getValue());
    }

    /**
     * @param $restUpTo
     * @return \Mockery\MockInterface|RestPower
     */
    private function createRestPower($restUpTo = null)
    {
        $restingPower = $this->mockery(RestPower::class);
        if ($restUpTo !== null) {
            $restingPower->shouldReceive('getRestUpTo')
                ->andReturn($restUpTo);
        }

        return $restingPower;
    }

    /**
     * @test
     */
    public function I_can_get_malus_on_decreased_stamina()
    {
        $stamina = $this->createStaminaToTest(5);
        $stamina->addFatigue($this->createFatigue(4));
        self::assertSame(0, $stamina->getMalusFromFatigue()->getValue());

        $stamina->changeFatigueBoundary($this->createFatigueBoundary(4));
        self::assertSame(4, $stamina->getFatigueBoundaryValue());
        self::assertSame(ReasonToRollAgainstFatigueMalus::FATIGUE, $stamina->getReasonToRollAgainstFatigueMalus()->getValue());
    }

    /**
     * @test
     */
    public function I_can_be_cleaned_from_malus_on_increased_stamina()
    {
        $stamina = $this->createStaminaToTest(5);
        $stamina->addFatigue($this->createFatigue(5));
        $stamina->rollAgainstMalusFromFatigue($this->createWill(3), $this->createRoller2d6Plus(6));
        self::assertSame(-2, $stamina->getMalusFromFatigue()->getValue());

        $stamina->changeFatigueBoundary($this->createFatigueBoundary(6));
        self::assertSame(6, $stamina->getFatigueBoundaryValue());
        self::assertSame(0, $stamina->getMalusFromFatigue()->getValue());
    }

    /**
     * @test
     */
    public function Nothing_changes_if_trying_to_change_fatigue_boundary_to_very_same()
    {
        $stamina = $this->createStaminaToTest(123);
        $stamina->changeFatigueBoundary($fatigueBoundary = $this->createFatigueBoundary(123));
        self::assertSame(123, $stamina->getFatigueBoundaryValue());
        $this->assertRested($stamina, $fatigueBoundary);
    }

    /**
     * @test
     */
    public function I_can_be_exhausted_out_of_imagination()
    {
        $stamina = $this->createStaminaToTest(5);
        $stamina->addFatigue($this->createFatigue(50));
        self::assertSame(50, $stamina->getFatigue()->getValue());
        self::assertFalse($stamina->isConscious());
        self::assertFalse($stamina->isAlive());
        self::assertSame(0, $stamina->getRemainingStaminaAmount());
    }
}