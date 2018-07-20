<?php
declare(strict_types = 1);

namespace DrdPlus\Tests\Stamina;

use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Stamina\Fatigue;
use DrdPlus\Stamina\GridOfFatigue;
use DrdPlus\Stamina\Stamina;
use Granam\Tests\Tools\TestWithMockery;

class GridOfFatigueTest extends TestWithMockery
{

    /**
     * @test
     */
    public function I_can_get_maximum_of_fatigues_per_row(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(123);
        $gridOfFatigueWithoutFatigueAtAll = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(123, $gridOfFatigueWithoutFatigueAtAll->getFatiguePerRowMaximum($fatigueBoundary));
    }

    /**
     * @param $fatigueBoundaryValue
     * @return \Mockery\MockInterface|FatigueBoundary
     */
    private function createFatigueBoundary(int $fatigueBoundaryValue): FatigueBoundary
    {
        $fatigueBoundary = $this->mockery(FatigueBoundary::class);
        $fatigueBoundary->shouldReceive('getValue')
            ->andReturn($fatigueBoundaryValue);

        return $fatigueBoundary;
    }

    /**
     * @param int $unrestedFatigue
     * @return \Mockery\MockInterface|Stamina
     */
    private function createStamina(int $unrestedFatigue): Stamina
    {
        $stamina = $this->mockery(Stamina::class);
        $stamina->shouldReceive('getFatigue')
            ->andReturn($fatigue = $this->mockery(Fatigue::class));
        $fatigue->shouldReceive('getValue')
            ->andReturn($unrestedFatigue);

        return $stamina;
    }

    /**
     * @test
     */
    public function I_can_get_calculated_filled_half_rows_for_given_fatigue_value(): void
    {
        // limit of fatigues divisible by two (odd)
        $fatigueBoundary = $this->createFatigueBoundary(124);

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(6, $gridOfFatigue->calculateFilledHalfRowsFor(492, $fatigueBoundary), 'Expected cap of half rows');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(0, $gridOfFatigue->calculateFilledHalfRowsFor(0, $fatigueBoundary), 'Expected no half row');

        $fatigueBoundary = $this->createFatigueBoundary(22);
        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(1, $gridOfFatigue->calculateFilledHalfRowsFor(11, $fatigueBoundary), 'Expected two half rows');

        $fatigueBoundary = $this->createFatigueBoundary(4);
        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(5, $gridOfFatigue->calculateFilledHalfRowsFor(10, $fatigueBoundary), 'Expected five half rows');

        // even limit of fatigues
        $fatigueBoundary = $this->createFatigueBoundary(111);
        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(6, $gridOfFatigue->calculateFilledHalfRowsFor(999, $fatigueBoundary), 'Expected cap of half rows');

        $fatigueBoundary = $this->createFatigueBoundary(333);
        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(0, $gridOfFatigue->calculateFilledHalfRowsFor(5, $fatigueBoundary), 'Expected no half row');

        $fatigueBoundary = $this->createFatigueBoundary(13);

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(0, $gridOfFatigue->calculateFilledHalfRowsFor(6, $fatigueBoundary), '"first" half of row should be rounded up');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(1, $gridOfFatigue->calculateFilledHalfRowsFor(7, $fatigueBoundary));

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(2, $gridOfFatigue->calculateFilledHalfRowsFor(13, $fatigueBoundary), 'Same value as row of fatigue should take two halves of such value even if even');

        $fatigueBoundary = $this->createFatigueBoundary(5);

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(2, $gridOfFatigue->calculateFilledHalfRowsFor(7, $fatigueBoundary), '"third" half or row should be rounded up');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(3, $gridOfFatigue->calculateFilledHalfRowsFor(8, $fatigueBoundary));

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */));
        self::assertSame(4, $gridOfFatigue->calculateFilledHalfRowsFor(10, $fatigueBoundary));
    }

    /**
     * @test
     */
    public function I_can_get_number_of_filled_rows(): void
    {
        $fatigueBoundary = $this->createFatigueBoundary(23);

        $gridOfFatigue = new GridOfFatigue($this->createStamina(4));
        self::assertSame(0, $gridOfFatigue->getNumberOfFilledRows($fatigueBoundary));

        $gridOfFatigue = new GridOfFatigue($this->createStamina(41));
        self::assertSame(1, $gridOfFatigue->getNumberOfFilledRows($fatigueBoundary));

        $gridOfFatigue = new GridOfFatigue($this->createStamina(46));
        self::assertSame(2, $gridOfFatigue->getNumberOfFilledRows($fatigueBoundary));

        $gridOfFatigue = new GridOfFatigue($this->createStamina(546));
        self::assertSame(3, $gridOfFatigue->getNumberOfFilledRows($fatigueBoundary), 'Maximum of rows should not exceed 3');
    }
}