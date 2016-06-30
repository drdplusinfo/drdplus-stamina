<?php
namespace DrdPlus\Tests\Stamina;

use DrdPlus\Stamina\Fatigue;
use DrdPlus\Stamina\GridOfFatigue;
use DrdPlus\Stamina\Stamina;
use Granam\Tests\Tools\TestWithMockery;

class GridOfFatigueTest extends TestWithMockery
{

    /**
     * @test
     */
    public function I_can_get_maximum_of_fatigues_per_row()
    {
        $gridOfFatigueWithoutFatigueAtAll = new GridOfFatigue($this->createStamina(0 /* no fatigue */, $fatigueBoundaryValue = 'foo'));
        self::assertSame($fatigueBoundaryValue, $gridOfFatigueWithoutFatigueAtAll->getFatiguePerRowMaximum());
    }

    /**
     * @param int $unrestedFatigue
     * @param $fatigueBoundaryValue
     * @return \Mockery\MockInterface|Stamina
     */
    private function createStamina($unrestedFatigue, $fatigueBoundaryValue = false)
    {
        $stamina = $this->mockery(Stamina::class);
        $stamina->shouldReceive('getFatigue')
            ->andReturn($fatigue = $this->mockery(Fatigue::class));
        $fatigue->shouldReceive('getValue')
            ->andReturn($unrestedFatigue);
        if ($fatigueBoundaryValue !== false) {
            $stamina->shouldReceive('getFatigueBoundaryValue')
                ->andReturn($fatigueBoundaryValue);
        }

        return $stamina;
    }

    /**
     * @test
     */
    public function I_can_get_calculated_filled_half_rows_for_given_fatigue_value()
    {
        // limit of fatigues divisible by two (odd)
        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 124));
        self::assertSame(6, $gridOfFatigue->calculateFilledHalfRowsFor(492), 'Expected cap of half rows');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 124));
        self::assertSame(0, $gridOfFatigue->calculateFilledHalfRowsFor(0), 'Expected no half row');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 22));
        self::assertSame(1, $gridOfFatigue->calculateFilledHalfRowsFor(11), 'Expected two half rows');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 4));
        self::assertSame(5, $gridOfFatigue->calculateFilledHalfRowsFor(10), 'Expected five half rows');

        // even limit of fatigues
        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 111));
        self::assertSame(6, $gridOfFatigue->calculateFilledHalfRowsFor(999), 'Expected cap of half rows');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 333));
        self::assertSame(0, $gridOfFatigue->calculateFilledHalfRowsFor(5), 'Expected no half row');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 13));
        self::assertSame(0, $gridOfFatigue->calculateFilledHalfRowsFor(6), '"first" half of row should be rounded up');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 13));
        self::assertSame(1, $gridOfFatigue->calculateFilledHalfRowsFor(7));

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 13));
        self::assertSame(2, $gridOfFatigue->calculateFilledHalfRowsFor(13), 'Same value as row of fatigue should take two halves of such value even if even');

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 5), '"third" half or row should be rounded up');
        self::assertSame(2, $gridOfFatigue->calculateFilledHalfRowsFor(7));

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 5));
        self::assertSame(3, $gridOfFatigue->calculateFilledHalfRowsFor(8));

        $gridOfFatigue = new GridOfFatigue($this->createStamina(0 /* no fatigue */, 5));
        self::assertSame(4, $gridOfFatigue->calculateFilledHalfRowsFor(10));
    }

    /**
     * @test
     */
    public function I_can_get_number_of_filled_rows()
    {
        $gridOfFatigue = new GridOfFatigue($this->createStamina(4, 23));
        self::assertSame(0, $gridOfFatigue->getNumberOfFilledRows());

        $gridOfFatigue = new GridOfFatigue($this->createStamina(41, 23));
        self::assertSame(1, $gridOfFatigue->getNumberOfFilledRows());

        $gridOfFatigue = new GridOfFatigue($this->createStamina(46, 23));
        self::assertSame(2, $gridOfFatigue->getNumberOfFilledRows());

        $gridOfFatigue = new GridOfFatigue($this->createStamina(546, 23));
        self::assertSame(3, $gridOfFatigue->getNumberOfFilledRows(), 'Maximum of rows should not exceed 3');
    }
}
