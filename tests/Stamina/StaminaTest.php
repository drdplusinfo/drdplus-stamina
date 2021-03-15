<?php declare(strict_types=1);

namespace DrdPlus\Tests\Stamina;

use DrdPlus\Properties\Derived\Endurance;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Stamina\Fatigue;
use DrdPlus\Stamina\MalusFromFatigue;
use DrdPlus\Stamina\RestPower;
use DrdPlus\Stamina\Stamina;
use DrdPlus\Stamina\ReasonToRollAgainstMalusFromFatigue;
use DrdPlus\BaseProperties\Will;
use DrdPlus\Tables\Measurements\Wounds\Wounds;
use DrdPlus\Tables\Measurements\Wounds\WoundsBonus;
use DrdPlus\Tables\Measurements\Wounds\WoundsTable;
use DrdPlus\Tables\Tables;
use Granam\DiceRolls\Templates\Rolls\Roll2d6DrdPlus;
use Granam\TestWithMockery\TestWithMockery;

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
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum($fatigueBoundary), $fatigueBoundary->getValue());
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum($fatigueBoundary) * 3, $stamina->getStaminaMaximum($fatigueBoundary));
        self::assertSame($stamina->getGridOfFatigue()->getFatiguePerRowMaximum($fatigueBoundary) * 3, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertTrue($stamina->isAlive($fatigueBoundary));
        self::assertTrue($stamina->isConscious($fatigueBoundary));
        self::assertFalse($stamina->needsToRollAgainstMalusFromFatigue());
        self::assertNull($stamina->getReasonToRollAgainstMalusFromFatigue());
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
            'fresh' => [1, 0, true, true],
            'tired' => [1, 1, true, true],
            'unconscious' => [1, 2, false, true],
            'dead' => [1, 3, false, false],
        ];
    }

    /**
     * @param int $value
     * @return \Mockery\MockInterface|Fatigue
     */
    private function createFatigue(int $value)
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
     * @param int $willValue
     * @param int $rollValue
     * @param int $expectedMalus
     */
    public function I_should_roll_against_malus_from_fatigue_because_of_new_fatigue(int $willValue, int $rollValue, int $expectedMalus): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(10), $fatigueBoundary);
        self::assertTrue($stamina->needsToRollAgainstMalusFromFatigue());
        self::assertSame(ReasonToRollAgainstMalusFromFatigue::getFatigueReason(), $stamina->getReasonToRollAgainstMalusFromFatigue());
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue(
                $this->createWill($willValue),
                $this->createRoll2d6Plus($rollValue),
                $fatigueBoundary
            )->getValue()
        );
        self::assertFalse($stamina->needsToRollAgainstMalusFromFatigue());
        self::assertNull($stamina->getReasonToRollAgainstMalusFromFatigue());
    }

    public function provideDecreasingRollAgainstMalusData(): array
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
    private function createWill($value = null): Will
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
     * @return \Mockery\MockInterface|Roll2d6DrdPlus
     */
    private function createRoll2d6Plus($value = null): Roll2d6DrdPlus
    {
        $roll = $this->mockery(Roll2d6DrdPlus::class);
        if ($value !== null) {
            $roll->shouldReceive('getValue')
                ->andReturn($value);
            $roll->shouldReceive('getRolledNumbers')
                ->andReturn([$value]);
        }

        return $roll;
    }

    /**
     * @test
     * @dataProvider provideIncreasingRollAgainstMalusData
     * @param int $willValue
     * @param int $rollValue
     * @param int $expectedMalus
     */
    public function I_should_roll_against_malus_from_fatigue_because_of_rest(int $willValue, int $rollValue, int $expectedMalus): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoll2d6Plus(3), $fatigueBoundary); // -3 malus as a result
        self::assertFalse($stamina->needsToRollAgainstMalusFromFatigue());
        $stamina->rest(
            $this->createRestPower(1),
            $fatigueBoundary,
            $this->createEndurance(5),
            $this->createTablesWithWoundsTable(6, 1 /* rests up to */)
        );
        self::assertTrue($stamina->needsToRollAgainstMalusFromFatigue());
        self::assertSame(ReasonToRollAgainstMalusFromFatigue::getRestReason(), $stamina->getReasonToRollAgainstMalusFromFatigue());
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue(
                $this->createWill($willValue),
                $this->createRoll2d6Plus($rollValue),
                $fatigueBoundary
            )->getValue()
        );
        self::assertFalse($stamina->needsToRollAgainstMalusFromFatigue());
        self::assertNull($stamina->getReasonToRollAgainstMalusFromFatigue());
    }

    public function provideIncreasingRollAgainstMalusData(): array
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
    private function createTablesWithWoundsTable(int $expectedWoundsBonus, int $resultingWoundsValue): Tables
    {
        $tables = $this->mockery(Tables::class);
        $tables->shouldReceive('getWoundsTable')
            ->andReturn($woundsTable = $this->mockery(WoundsTable::class));
        $woundsTable->shouldReceive('toWounds')
            ->zeroOrMoreTimes()
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
     */
    public function I_can_not_roll_on_malus_from_fatigue_if_not_needed(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $this->expectException(\DrdPlus\Stamina\Exceptions\UselessRollAgainstMalus::class);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(), $this->createRoll2d6Plus(), $fatigueBoundary);
    }

    // ROLL ON MALUS EXPECTED

    /**
     * @test
     */
    public function I_can_not_add_new_fatigue_if_roll_on_malus_expected(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(10), $fatigueBoundary);
        $this->expectException(\DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst::class);
        $stamina->addFatigue($this->createFatigue(10), $fatigueBoundary);
    }

    /**
     * @test
     */
    public function I_can_not_rest_if_roll_on_malus_expected(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $this->expectException(\DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst::class);
        $stamina->rest($this->createRestPower(5), $fatigueBoundary, $this->createEndurance(-2), $this->createTablesWithWoundsTable(3, 0));
    }

    /**
     * @test
     */
    public function I_can_not_get_malus_from_fatigue_if_roll_on_it_expected(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(10);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(14), $fatigueBoundary);
        $this->expectException(\DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst::class);
        $stamina->getMalusFromFatigue($fatigueBoundary);
    }

    // MALUS CONDITIONAL CHANGES

    /**
     * @test
     * @dataProvider provideRollForMalus
     * @param int $willValue
     * @param int $rollValue
     * @param int $expectedMalus
     */
    public function Malus_can_increase_on_new_fatigue(int $willValue, int $rollValue, int $expectedMalus): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        $stamina->addFatigue($this->createFatigue(5), $fatigueBoundary);
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue($this->createWill($willValue), $this->createRoll2d6Plus($rollValue), $fatigueBoundary)->getValue()
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
                $stamina->rollAgainstMalusFromFatigue($this->createWill($currentWillValue), $this->createRoll2d6Plus($currentRollValue), $fatigueBoundary)->getValue(),
                "For will $currentWillValue and roll $currentRollValue has been expected malus $currentlyExpectedMalus"
            );
            self::assertSame($currentlyExpectedMalus, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
            $stamina->rest($this->createRestPower(3), $fatigueBoundary, $this->createEndurance(2), $this->createTablesWithWoundsTable(5, 3)); // "resetting" currently given fatigue
            // low values to ensure untouched malus (should not be increased, therefore not changed here at all, on rest)
            $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoll2d6Plus(-1), $fatigueBoundary);
        }
    }

    public function provideRollForMalus(): array
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
     * @param int $willValue
     * @param int $rollValue
     * @param int $expectedMalus
     */
    public function Malus_can_not_decrease_on_new_fatigue(int $willValue, int $rollValue, int $expectedMalus): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        $stamina->addFatigue($this->createFatigue(5), $fatigueBoundary);
        self::assertSame(
            $expectedMalus,
            $stamina->rollAgainstMalusFromFatigue($this->createWill($willValue), $this->createRoll2d6Plus($rollValue), $fatigueBoundary)->getValue()
        );
        self::assertSame($expectedMalus, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());

        for ($currentWillValue = $willValue, $currentRollValue = $rollValue;
             $currentRollValue < 16 && $currentWillValue < 10;
             $currentRollValue++, $currentWillValue++
        ) {
            $stamina->addFatigue($this->createFatigue(3), $fatigueBoundary);
            self::assertSame(
                $expectedMalus, // malus should not be decreased (be closer to zero)
                $stamina->rollAgainstMalusFromFatigue($this->createWill($currentWillValue), $this->createRoll2d6Plus($currentRollValue), $fatigueBoundary)->getValue(),
                "Even for will $currentWillValue and roll $currentRollValue has been expected previous malus $expectedMalus"
            );
            self::assertSame($expectedMalus, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
            $stamina->rest($this->createRestPower(3), $fatigueBoundary, $this->createEndurance(-1), $this->createTablesWithWoundsTable(2, 3)); // "resetting" currently given fatigue
            // low values to ensure untouched malus (should not be increased, therefore changed here at all, on rest)
            $stamina->rollAgainstMalusFromFatigue($this->createWill(-1), $this->createRoll2d6Plus(-1), $fatigueBoundary);
        }
    }

    /**
     * @test
     */
    public function Malus_is_not_increased_on_new_rest_by_worse_roll(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());

        // 3x fatigue to reach some malus
        $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(0), $this->createRoll2d6Plus(11), $fatigueBoundary);
        self::assertSame(-1, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
        self::assertSame(1, $stamina->rest($this->createRestPower(1), $fatigueBoundary, $this->createEndurance(6), $this->createTablesWithWoundsTable(7, 1)));
        $stamina->rollAgainstMalusFromFatigue($this->createWill(0), $this->createRoll2d6Plus(-2), $fatigueBoundary); // much worse roll
        self::assertSame(-1, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue(), 'Malus should not be increased');
    }

    /**
     * @test
     */
    public function I_can_be_fatigued(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        self::assertSame(2, $stamina->addFatigue($this->createFatigue(2), $fatigueBoundary)->getValue());
        self::assertSame(2, $stamina->getFatigue()->getValue());
        self::assertSame(13, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
        self::assertFalse($stamina->needsToRollAgainstMalusFromFatigue());
        self::assertNull($stamina->getReasonToRollAgainstMalusFromFatigue());

        self::assertSame(3, $stamina->addFatigue($this->createFatigue(1), $fatigueBoundary)->getValue());
        self::assertSame(3, $stamina->getFatigue()->getValue());
        self::assertSame(12, $stamina->getRemainingStaminaAmount($fatigueBoundary));
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue());
        self::assertFalse($stamina->needsToRollAgainstMalusFromFatigue());
        self::assertNull($stamina->getReasonToRollAgainstMalusFromFatigue());
    }

    /**
     * @test
     */
    public function I_can_rest(): void
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
        self::assertFalse($stamina->needsToRollAgainstMalusFromFatigue());
        self::assertNull($stamina->getReasonToRollAgainstMalusFromFatigue());

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
    public function I_can_rest_so_little_so_nothing_changes(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(4);
        $stamina = $this->createStaminaToTest($fatigueBoundary);

        $stamina->addFatigue($this->createFatigue(7), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(-2), $this->createRoll2d6Plus(5), $fatigueBoundary);
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
    public function I_can_be_exhausted_out_of_imagination(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        $stamina->addFatigue($this->createFatigue(999), $fatigueBoundary);
        self::assertSame(999, $stamina->getFatigue()->getValue());
        self::assertFalse($stamina->isConscious($fatigueBoundary));
        self::assertFalse($stamina->isAlive($fatigueBoundary));
        self::assertSame(0, $stamina->getRemainingStaminaAmount($fatigueBoundary));
    }

    /**
     * @test
     */
    public function I_can_ask_it_if_may_suffer_from_fatigue(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        self::assertFalse($stamina->maySufferFromFatigue($fatigueBoundary));
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        self::assertFalse($stamina->maySufferFromFatigue($fatigueBoundary));
        $stamina->addFatigue($this->createFatigue(1), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(999), $this->createRoll2d6Plus(999), $fatigueBoundary);
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue(), 'No malus expected due to a really high roll and will');
        self::assertTrue(
            $stamina->maySufferFromFatigue($fatigueBoundary),
            'There should be already a chance to suffer from fatigue as the fatigue boundary is same as fatigue (first row is filled)'
        );
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(999), $this->createRoll2d6Plus(999), $fatigueBoundary);
        self::assertTrue(
            $stamina->maySufferFromFatigue($fatigueBoundary),
            'There should be a chance to suffer from fatigue as two rows are filled'
        );
        self::assertTrue($stamina->isConscious($fatigueBoundary));
        $stamina->addFatigue($this->createFatigue(1), $fatigueBoundary);
        self::assertFalse($stamina->isConscious($fatigueBoundary));
        self::assertFalse(
            $stamina->maySufferFromFatigue($fatigueBoundary),
            'I should not suffer from fatigue as I am unconscious'
        );
    }

    /**
     * @test
     */
    public function I_can_ask_it_if_I_am_suffering_from_fatigue(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(5);
        $stamina = $this->createStaminaToTest($fatigueBoundary);
        self::assertFalse($stamina->mayHaveMalusFromFatigue($fatigueBoundary));
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        self::assertFalse($stamina->mayHaveMalusFromFatigue($fatigueBoundary));
        $stamina->addFatigue($this->createFatigue(1), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(999), $this->createRoll2d6Plus(999), $fatigueBoundary);
        self::assertSame(0, $stamina->getMalusFromFatigue($fatigueBoundary)->getValue(), 'No malus expected due to a really high roll and will');
        self::assertTrue(
            $stamina->mayHaveMalusFromFatigue($fatigueBoundary),
            'There should be already a chance to suffer from fatigue as the fatigue boundary is same as fatigue (first row is filled)'
        );
        $stamina->addFatigue($this->createFatigue(4), $fatigueBoundary);
        $stamina->rollAgainstMalusFromFatigue($this->createWill(999), $this->createRoll2d6Plus(999), $fatigueBoundary);
        self::assertTrue(
            $stamina->mayHaveMalusFromFatigue($fatigueBoundary),
            'There should be a chance to suffer from fatigue as two rows are filled'
        );
        self::assertTrue($stamina->isConscious($fatigueBoundary));
        $stamina->addFatigue($this->createFatigue(1), $fatigueBoundary);
        self::assertFalse($stamina->isConscious($fatigueBoundary));
        self::assertTrue(
            $stamina->mayHaveMalusFromFatigue($fatigueBoundary),
            'I should still have (non-applicable) malus fatigue even as unconscious'
        );
    }
}
