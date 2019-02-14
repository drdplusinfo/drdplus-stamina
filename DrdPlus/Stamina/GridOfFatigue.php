<?php
declare(strict_types=1);

namespace DrdPlus\Stamina;

use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Calculations\SumAndRound;
use Granam\Integer\IntegerInterface;
use Granam\Strict\Object\StrictObject;

class GridOfFatigue extends StrictObject
{

    public const PAIN_NUMBER_OF_ROWS = 1;
    public const UNCONSCIOUS_NUMBER_OF_ROWS = 2;
    public const TOTAL_NUMBER_OF_ROWS = 3;

    /**
     * @var Stamina
     */
    private $stamina;

    public function __construct(Stamina $stamina)
    {
        $this->stamina = $stamina;
    }

    public function getFatiguePerRowMaximum(FatigueBoundary $fatigueBoundary): int
    {
        return $fatigueBoundary->getValue();
    }

    /**
     * @param int|IntegerInterface $fatigueValue
     * @param FatigueBoundary $fatigueBoundary
     * @return int
     */
    public function calculateFilledHalfRowsFor($fatigueValue, FatigueBoundary $fatigueBoundary): int
    {
        $fatiguePerRowMaximum = $this->getFatiguePerRowMaximum($fatigueBoundary);
        if ($fatiguePerRowMaximum % 2 === 0) { // odd
            $filledHalfRows = SumAndRound::floor($fatigueValue / ($fatiguePerRowMaximum / 2));
        } else {
            // first half round up, second down (for example 11 = 6 + 5)
            $halves = [SumAndRound::ceiledHalf($fatiguePerRowMaximum), SumAndRound::flooredHalf($fatiguePerRowMaximum)];
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

    public function getNumberOfFilledRows(FatigueBoundary $fatigueBoundary): int
    {
        $numberOfFilledRows = SumAndRound::floor($this->stamina->getFatigue()->getValue() / $this->getFatiguePerRowMaximum($fatigueBoundary));

        return $numberOfFilledRows < self::TOTAL_NUMBER_OF_ROWS
            ? $numberOfFilledRows
            : self::TOTAL_NUMBER_OF_ROWS;
    }

}