<?php
namespace DrdPlus\Tests\Stamina;

use DrdPlus\Stamina\RestPower;
use DrdPlus\Tables\Measurements\Wounds\Wounds;
use DrdPlus\Tables\Measurements\Wounds\WoundsBonus;
use DrdPlus\Tables\Measurements\Wounds\WoundsTable;
use Granam\Tests\Tools\TestWithMockery;

class RestingPowerTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it()
    {
        $healingPower = new RestPower(123, $this->createWoundsTable(987, 123));
        self::assertSame(123, $healingPower->getValue());
        self::assertSame('123', (string)$healingPower);
        self::assertSame(987, $healingPower->getRestUpTo());
    }

    /**
     * @param $woundsValue
     * @param $expectedWoundsBonus
     * @return \Mockery\MockInterface|WoundsTable
     */
    private function createWoundsTable($woundsValue, $expectedWoundsBonus)
    {
        $woundsTable = $this->mockery(WoundsTable::class);
        $woundsTable->shouldReceive('toWounds')
            ->andReturnUsing(function (WoundsBonus $woundBonus) use ($expectedWoundsBonus, $woundsValue) {
                self::assertSame($expectedWoundsBonus, $woundBonus->getValue());
                $wounds = $this->mockery(Wounds::class);
                $wounds->shouldReceive('getValue')
                    ->andReturn($woundsValue);
                $wounds->shouldReceive('getBonus')
                    ->andReturn($woundBonus);

                return $wounds;
            });

        return $woundsTable;
    }

    /**
     * @test
     */
    public function I_can_get_new_instance_with_decreased_power_by_healed_amount()
    {
        $healingPower = new RestPower(123, $woundsTable = $this->createWoundsTable(987, 123));
        self::assertSame(123, $healingPower->getValue());

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $notDecreased = $healingPower->decreaseByRestedAmount(0);
        self::assertSame($healingPower, $notDecreased, 'It should be the very same instance if no change happened at all');

        $woundsTable->shouldReceive('toBonus')
            ->andReturnUsing(function (Wounds $wounds) {
                self::assertSame(900, $wounds->getValue(), 'Expected original heal-up-to decreased by healed amount');

                $woundBonus = $this->mockery(WoundsBonus::class);
                $woundBonus->shouldReceive('getValue')
                    ->andReturn(333);

                return $woundBonus;
            });
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $decreased = $healingPower->decreaseByRestedAmount(87);
        self::assertNotEquals($healingPower, $decreased, 'It should not has same value nor be the same instance');
        self::assertSame(333, $decreased->getValue());
    }

    /**
     * @test
     * @expectedException \DrdPlus\Stamina\Exceptions\RestedAmountIsTooBig
     */
    public function I_can_not_get_new_instance_by_strangely_high_healed_amount()
    {
        $healingPower = new RestPower(123, $woundsTable = $this->createWoundsTable(10, 123));
        $healingPower->decreaseByRestedAmount(11);
    }

    /**
     * @test
     */
    public function I_can_continually_spent_all_the_healing_power()
    {
        $healingPower = new RestPower(26, new WoundsTable());
        self::assertSame(26, $healingPower->getValue());
        while ($healingPower->getRestUpTo() > 0) {
            $previousHealUpTo = $healingPower->getRestUpTo();
            $healingPower = $healingPower->decreaseByRestedAmount(1);
            self::assertSame(
                $previousHealUpTo - 1,
                $healingPower->getRestUpTo(),
                "Expected new 'heal up to' to be one less than previous $previousHealUpTo"
            );
        }
    }
}
