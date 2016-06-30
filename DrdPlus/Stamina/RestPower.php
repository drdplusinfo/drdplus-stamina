<?php
namespace DrdPlus\Stamina;

use DrdPlus\Tables\Measurements\Wounds\Wounds as TableWounds;
use DrdPlus\Tables\Measurements\Wounds\WoundsBonus;
use DrdPlus\Tables\Measurements\Wounds\WoundsTable;
use Granam\Integer\IntegerInterface;
use Granam\Integer\Tools\ToInteger;
use Granam\Strict\Object\StrictObject;

class RestPower extends StrictObject implements IntegerInterface
{
    /**
     * @var TableWounds
     */
    private $restUpToFatigue;
    /**
     * @var WoundsTable
     */
    private $woundsTable;

    /**
     * HealingPower constructor.
     * @param int $healingPowerValue
     * @param WoundsTable $woundsTable
     */
    public function __construct($healingPowerValue, WoundsTable $woundsTable)
    {
        $this->restUpToFatigue = (new WoundsBonus($healingPowerValue, $woundsTable))->getWounds();
        $this->woundsTable = $woundsTable;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->restUpToFatigue->getBonus()->getValue();
    }

    /**
     * @return int
     */
    public function getRestUpTo()
    {
        return $this->restUpToFatigue->getValue();
    }

    /**
     * @param int $healedAmount not a healing power, but real amount of healed wound points
     * @return static|RestPower
     * @throws \DrdPlus\Stamina\Exceptions\RestedAmountIsTooBig
     * @throws \Granam\Integer\Tools\Exceptions\WrongParameterType
     * @throws \Granam\Integer\Tools\Exceptions\ValueLostOnCast
     */
    public function decreaseByRestedAmount($healedAmount)
    {
        $healedAmount = ToInteger::toInteger($healedAmount);
        if ($healedAmount > $this->getRestUpTo()) {
            throw new Exceptions\RestedAmountIsTooBig(
                "So much amount {$healedAmount} could not be healed by this healing power ({$this->getValue()}) able to heal only up to {$this->getRestUpTo()}"
            );
        }
        if ($healedAmount === 0) {
            return $this;
        }
        $remainingHealUpTo = $this->getRestUpTo() - $healedAmount;
        $decreasedHealingPower = clone $this;
        $decreasedHealingPower->restUpToFatigue = new TableWounds($remainingHealUpTo, $this->woundsTable);

        return $decreasedHealingPower;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }
}