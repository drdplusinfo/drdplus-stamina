<?php
namespace DrdPlus\Tests\Stamina;

use DrdPlus\DiceRolls\Templates\Rollers\Roller2d6DrdPlus;
use DrdPlus\DiceRolls\Templates\Rolls\Roll2d6DrdPlus;
use DrdPlus\Properties\Derived\Endurance;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Stamina\Fatigue;
use DrdPlus\Stamina\MalusFromFatigue;
use DrdPlus\Stamina\RestPower;
use DrdPlus\Stamina\Stamina;
use DrdPlus\Stamina\ReasonToRollAgainstFatigueMalus;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Tables\Measurements\Wounds\Wounds;
use DrdPlus\Tables\Measurements\Wounds\WoundsBonus;
use DrdPlus\Tables\Measurements\Wounds\WoundsTable;
use DrdPlus\Tables\Tables;
use Granam\Tests\Tools\TestWithMockery;

/** @noinspection LongInheritanceChainInspection */
class StaminaTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(123);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        self::assertSame(369, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertSame(369, $stamina->getStaminaMaximum($fatigueBoundary));
        self::assertInstanceOf(Fatigue::class, $fatigue = $stamina->getFatigue());
        self::assertSame(0, $fatigue->getValue());
        self::assertInstanceOf(MalusFromFatigue::class, $malusFromFatigue = $stamina->getMalusFromFatigue($fatigueBoundary));
        self::assertSame(0, $malusFromFatigue->getValue());
    }

    /**
     * @param FatigueBoundary $fatigueBoundary
     * @return Stamina
     */
    private function createStaminaToTest(FatigueBoundary $fatigueBoundary): Stamina
    {
        $stamina = new Stamina();
        $this->assertRested($stamina, $fatigueBoundary);

        return $stamina;
    }

    /**
     * @param int $value
     * @return \Mockery\MockInterface|FatigueBoundary
     */
    private function createFatigueBoundary(int $value): FatigueBoundary
    {
        $fatigueBoundary = $this->mockery(FatigueBoundary::class);
        $fatigueBoundary->shouldReceive('getValue')
            ->andReturn($value);

        return $fatigueBoundary;
    }

    private function assertRested(Stamina $stamina, FatigueBoundary $fatigueBoundary): void
    {
        self::assertNull($stamina->getId(), 'Not yet persisted stamina should not has filled ID (it is database responsibility in this case)');
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum($fatigueBoundary), $fatigueBoundary->getValue());
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum($fatigueBoundary) * 3, $stamina->getStaminaMaximum($fatigueBoundary));
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum($fatigueBoundary) * 3, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertTrue($stamina->isAlive($fatigueBoundary));
        self::assertTrue($stamina->isConscious($fatigueBoundary));
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());
    }

    /**
     * @test
     * @dataProvider provideConsciousAndAlive
     * @param int $fatigueBoundaryValue
     * @param int $fatigueValue
     * @param bool $isConscious
     * @param bool $isAlive
     */
    public function I_can_easily_find_out_if_creature_is_conscious_and_alive(int $fatigueBoundaryValue, int $fatigueValue, bool $isConscious, bool $isAlive): void
    {
        $fatigueBoundary = $this->createFatigueBoundary($fatigueBoundaryValue);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue($fatigueValue), $fatigueBoundary);

        self::assertSame($isConscious, $stamina->isConscious($fatigueBoundary));
        self::assertSame($isAlive, $stamina->isAlive($fatigueBoundary));
    }

    public function provideConsciousAndAlive(): array
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
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(10), $fatigueBoundary);
        self::assertTrue($stamina->needsToRollAgainstMalus());
        self::assertSame(ReasonToRollAgainstFatigueMalus::getFatigueReason(), $stamina->getReasonToRollAgainstFatigueMalus());
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue(
                $this->createWill($willValue),
                $this->createRoller2d6Plus($rollValue),
                $fatigueBoundary
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
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoller2d6Plus(3), $fatigueBoundary); // -3 malus as a result
        self::assertFalse($stamina->needsToRollAgainstMalus());
        $stamina->rest(
            $this->createRestPower(1),
            $fatigueBoundary,
            $this->createEndurance(5),
            $this->createTablesWithWoundsTable(6, 1 /* rests up to */)
        );
        self::assertTrue($stamina->needsToRollAgainstMalus());
        self::assertSame(ReasonToRollAgainstFatigueMalus::getRestReason(), $stamina->getReasonToRollAgainstFatigueMalus());
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue(
                $this->createWill($willValue),
                $this->createRoller2d6Plus($rollValue),
                $fatigueBoundary
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
     * @param $value
     * @return \Mockery\MockInterface|Endurance
     */
    private function createEndurance($value)
    {
        $endurance = $this->mockery(Endurance::class);
        $endurance->shouldReceive('getValue')
            ->andReturn($value);

        return $endurance;
    }

    /**
     * @param $expectedWoundsBonus
     * @param $resultingWoundsValue
     * @return \Mockery\MockInterface|Tables
     */
    private function createTablesWithWoundsTable($expectedWoundsBonus, $resultingWoundsValue)
    {
        $tables = $this->mockery(Tables::class);
        $tables->shouldReceive('getWoundsTable')
            ->andReturn($woundsTable = $this->mockery(WoundsTable::class));
        $woundsTable->shouldReceive('toWounds')
            ->andReturnUsing(function (WoundsBonus $woundsBonus) use ($expectedWoundsBonus, $resultingWoundsValue) {
                self::assertSame($expectedWoundsBonus, $woundsBonus->getValue());
                $wounds = $this->mockery(Wounds::class);
                $wounds->shouldReceive('getValue')
                    ->andReturn($resultingWoundsValue);

                return $wounds;
            });

        return $tables;
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\UselessRollAgainstMalus
     */
    public function I_can_not_roll_on_malus_from_fatigue_if_not_needed()
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(), $this->createRoller2d6Plus(), $fatigueBoundary);
    }

    // ROLL ON MALUS EXPECTED

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function I_can_not_add_new_fatigue_if_roll_on_malus_expected()
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        try {
            $stamina->addFatigue($this->createFatigue(10), $fatigueBoundary);
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getTraceAsString());
        }
        $stamina->addFatigue($this->createFatigue(10), $fatigueBoundary);
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function I_can_not_rest_if_roll_on_malus_expected()
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        try {
            $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
            $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
            $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getTraceAsString());
        }
        $stamina->rest($this->createRestPower(5), $fatigueBoundary, $this->createEndurance(-2), $this->createTablesWithWoundsTable(3, 0));
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function I_can_not_get_malus_from_fatigue_if_roll_on_it_expected()
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        try {
            $stamina->addFatigue($this->createFatigue(14), $fatigueBoundary);
        } catch (\Exception $exception) {
            self::fail('No exception expected so far: ' . $exception->getTraceAsString());
        }
        $stamina->getMalusFromFatigue($fatigueBoundary);
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
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        $stamina->addFatigue($this->createFatigue(5), $fatigueBoundary);
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue($this->createWill($willValue), $this->createRoller2d6Plus($rollValue), $fatigueBoundary)->getValue()
        );
        self::assertSame($expectedMalus, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());

        for ($currentWillValue = $willValue, $currentRollValue = $rollValue;
             $currentRollValue > -2 && $currentWillValue > -2;
             $currentRollValue--, $currentWillValue--
        ) {
            $stamina->addFatigue($this->createFatigue(3), $fatigueBoundary);
            $currentlyExpectedMalus = max(0, min(3, (int)floor(($currentWillValue + $currentRollValue) / 5))) - 3; // 0; -1; -2; -3
            self::assertSame(
                $currentlyExpectedMalus, // malus can increase (be more negative)
                $stamina->rollAgainstMalusFromFatigue($this->createWill($currentWillValue), $this->createRoller2d6Plus($currentRollValue), $fatigueBoundary)->getValue(),
                "For will $currentWillValue and roll $currentRollValue has been expected malus $currentlyExpectedMalus"
            );
            self::assertSame($currentlyExpectedMalus, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
            $stamina->rest($this->createRestPower(3), $fatigueBoundary, $this->createEndurance(2), $this->createTablesWithWoundsTable(5, 3)); // "resetting" currently given fatigue
            // low values to ensure untouched malus (should not be increased, therefore not changed here at all, on rest)
            $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoller2d6Plus(-1), $fatigueBoundary);
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
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        $stamina->addFatigue($this->createFatigue(5), $fatigueBoundary);
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue($this->createWill($willValue), $this->createRoller2d6Plus($rollValue), $fatigueBoundary)->getValue()
        );
        self::assertSame($expectedMalus, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());

        for ($currentWillValue = $willValue, $currentRollValue = $rollValue;
             $currentRollValue < 16 && $currentWillValue < 10;
             $currentRollValue++, $currentWillValue++
        ) {
            $stamina->addFatigue($this->createFatigue(3), $fatigueBoundary);
            self::assertSame(
                $expectedMalus, // malus should not be decreased (be closer to zero)
                $stamina->rollAgainstMalusFromFatigue($this->createWill($currentWillValue), $this->createRoller2d6Plus($currentRollValue), $fatigueBoundary)->getValue(),
                "Even for will $currentWillValue and roll $currentRollValue has been expected previous malus $expectedMalus"
            );
            self::assertSame($expectedMalus, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
            $stamina->rest($this->createRestPower(3), $fatigueBoundary, $this->createEndurance(-1), $this->createTablesWithWoundsTable(2, 3)); // "resetting" currently given fatigue
            // low values to ensure untouched malus (should not be increased, therefore changed here at all, on rest)
            $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoller2d6Plus(-1), $fatigueBoundary);
        }
    }

    /**
     * @test
     */
    public function Malus_is_not_increased_on_new_rest_by_worse_roll()
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());

        // 3x fatigue to reach some malus
        $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(0), $this->createRoller2d6Plus(11), $fatigueBoundary);
        self::assertSame(-1, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
        self::assertSame(1, $stamina->rest($this->createRestPower(1), $fatigueBoundary, $this->createEndurance(6), $this->createTablesWithWoundsTable(7, 1)));
        $stamina->rollAgainstMalusFromFatigue($this->createWill(0), $this->createRoller2d6Plus(-2), $fatigueBoundary); // much worse roll
        self::assertSame(-1, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue(), 'Malus should not be increased');
    }

    /**
     * @test
     */
    public function I_can_be_fatigued()
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        self::assertSame(2, $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary)->getValue());
        self::assertSame(2, $stamina->getFatigue()->getValue());
        self::assertSame(13, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());

        self::assertSame(3, $stamina->addFatigue($this->createFatigue(1), $fatigueBoundary)->getValue());
        self::assertSame(3, $stamina->getFatigue()->getValue());
        self::assertSame(12, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());
    }

    /**
     * @test
     */
    public function I_can_rest()
    {
        $fatigueBoundary = $this->createFatigueBoundary(7);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        self::assertSame(21, $stamina->getRemainingStaminaAmount($fatigueBoundary));

        $stamina->addFatigue($this->createFatigue(1), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(3), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary);

        self::assertSame(15, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertSame(
            5,
            $stamina->rest(
                new RestPower(4),
                $fatigueBoundary,
                $this->createEndurance(-6),
                $this->createTablesWithWoundsTable(-2, 5)
            )
        );
        self::assertSame(20, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertSame(1, $stamina->getFatigue()->getValue());
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
        self::assertFalse($stamina->needsToRollAgainstMalus());
        self::assertNull($stamina->getReasonToRollAgainstFatigueMalus());

        self::assertSame(
            1,
            $stamina->rest(
                $this->createRestPower(10),
                $fatigueBoundary,
                $this->createEndurance(-1),
                $this->createTablesWithWoundsTable(9, 3 /* intentionally more rested points than needed */)
            )
        );
        self::assertSame(21, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertSame(0, $stamina->getFatigue()->getValue());
    }

    /**
     * @test
     */
    public function I_can_rest_so_little_so_nothing_changes()
    {
        $fatigueBoundary = $this->createFatigueBoundary(4);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        $stamina->addFatigue($this->createFatigue(7), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(-2), $this->createRoller2d6Plus(5), $fatigueBoundary);
        self::assertSame(-3, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());

        self::assertSame(0, $stamina->rest($this->createRestPower(0), $fatigueBoundary, $this->createEndurance(0), $this->createTablesWithWoundsTable(0, 0)));
        self::assertSame(-3, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
    }

    /**
     * @param $restUpTo
     * @return \Mockery\MockInterface|RestPower
     */
    private function createRestPower($restUpTo = null)
    {
        $restingPower = $this->mockery(RestPower::class);
        if ($restUpTo !== null) {
            $restingPower->shouldReceive('getValue')
                ->andReturn($restUpTo);
        }

        return $restingPower;
    }

    /**
     * @test
     */
    public function I_can_be_exhausted_out_of_imagination()
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(999), $fatigueBoundary);
        self::assertSame(999, $stamina->getFatigue()->getValue());
        self::assertFalse($stamina->isConscious($fatigueBoundary));
        self::assertFalse($stamina->isAlive($fatigueBoundary));
        self::assertSame(0, $stamina->getRemainingStaminaAmount($fatigueBoundary));
    }
}