<?php
declare(strict_types=1);

namespace DrdPlus\Tests\Stamina;

use DrdPlus\Stamina\RestPower;
use Granam\Tests\Tools\TestWithMockery;

class RestPowerTest extends TestWithMockery
{
    /**
     * @test
     */
    public function I_can_use_it(): void
    {
        $restingPower = new RestPower(123);
        self::assertSame(123, $restingPower->getValue());
        self::assertSame('123', (string)$restingPower);
    }
}