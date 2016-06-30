<?php
namespace DrdPlus\Stamina;

use DrdPlus\Tools\Calculations\SumAndRound;
use Granam\Strict\Object\StrictObject;

class GridOfFatigue extends StrictObject
{

    const PAIN_NUMBER_OF_ROWS = 1;
    const UNCONSCIOUS_NUMBER_OF_ROWS = 2;
    const TOTAL_NUMBER_OF_ROWS = 3;

    /**
     * @var Stamina
     */
    private $stamina;

    public function __construct(Stamina $stamina)
    {
        $this->stamina = $stamina;
    }

    /**
     * @return int
     */
    public function getFatiguePerRowMaximum()
    {
        return $this->stamina->getFatigueBoundaryValue();
    }

    /**
     * @param int $fatigueValue
     * @return int
     */
    public function calculateFilledHalfRowsFor($fatigueValue)
    {
        if ($this->getFatiguePerRowMaximum() % 2 === 0) { // odd
            $filledHalfRows = SumAndRound::floor($fatigueValue / ($this->getFatiguePerRowMaximum() / 2));
        } else {
            // first half round up, second down (for example 11 = 6 + 5)
            $halves = [SumAndRound::ceiledHalf($this->getFatiguePerRowMaximum()), SumAndRound::flooredHalf($this->getFatiguePerRowMaximum())];
            $filledHalfRows = 0;
            while ($fatigueValue > 0) {
                foreach ($halves as $half) {
                    $fatigueValue -= $half;
                    if ($fatigueValue < 0) {
                        break;
                    }
                    $filledHalfRows++;
                }
            }
        }

        return $filledHalfRows < (self::TOTAL_NUMBER_OF_ROWS * 2)
            ? $filledHalfRows
            : self::TOTAL_NUMBER_OF_ROWS * 2; // to prevent "more dead than death" value
    }

    /**
     * @return int
     */
    public function getNumberOfFilledRows()
    {
        $numberOfFilledRows = SumAndRound::floor($this->stamina->getFatigue() / $this->getFatiguePerRowMaximum());

        return $numberOfFilledRows < self::TOTAL_NUMBER_OF_ROWS
            ? $numberOfFilledRows
            : self::TOTAL_NUMBER_OF_ROWS;
    }

}