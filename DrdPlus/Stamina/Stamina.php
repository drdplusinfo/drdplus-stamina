<?php
namespace DrdPlus\Stamina;

use Doctrineum\Entity\Entity;
use Drd\DiceRoll\Templates\Rollers\Roller2d6DrdPlus;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Properties\Base\Will;
use DrdPlus\RollsOn\Traps\RollOnWillAgainstMalus;
use DrdPlus\RollsOn\Traps\RollOnWill;
use Granam\Strict\Object\StrictObject;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Stamina extends StrictObject implements Entity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $fatigue;
    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    private $fatigueBoundaryValue;
    /**
     * @var MalusFromFatigue
     * @ORM\Column(type="malus_from_fatigue")
     */
    private $malusFromFatigue;
    /**
     * @var ReasonToRollAgainstFatigueMalus|null
     * @ORM\Column(type="reason_to_roll_against_fatigue_malus", nullable=true)
     */
    private $reasonToRollAgainstFatigueMalus;
    /**
     * @var GridOfFatigue|null is just a helper, does not need to be persisted
     */
    private $gridOfFatigue;

    public function __construct(FatigueBoundary $fatigueBoundary)
    {
        $this->fatigue = 0;
        $this->fatigueBoundaryValue = $fatigueBoundary->getValue();
        $this->malusFromFatigue = MalusFromFatigue::getIt(0);
    }

    /**
     * @param FatigueSize $fatigueSize
     * @return int
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function addFatigue(FatigueSize $fatigueSize)
    {
        $this->checkIfNeedsToRollAgainstMalusFirst();
        $this->fatigue += $fatigueSize->getValue();
        $this->resolveMalusAfterFatigue($this->fatigue);

        return $this->fatigue;
    }

    /**
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    private function checkIfNeedsToRollAgainstMalusFirst()
    {
        if ($this->needsToRollAgainstMalus()) {
            throw new Exceptions\NeedsToRollAgainstMalusFirst('Need to roll on will against malus caused by fatigue');
        }
    }

    /**
     * @param int $fatigueAmount
     */
    private function resolveMalusAfterFatigue($fatigueAmount)
    {
        if ($fatigueAmount === 0) {
            return;
        }
        if ($this->maySufferFromFatigue()) {
            $this->reasonToRollAgainstFatigueMalus = ReasonToRollAgainstFatigueMalus::getFatigueReason();
        } elseif ($this->isConscious()) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->malusFromFatigue = MalusFromFatigue::getIt(0);
        } // otherwise left the previous malus - creature will suffer by it when comes conscious again
    }

    /**
     * @return bool
     */
    private function maySufferFromFatigue()
    {
        // if the creature became unconscious than the roll against pain malus is not re-rolled
        return $this->getGridOfFatigue()->getNumberOfFilledRows() >= GridOfFatigue::PAIN_NUMBER_OF_ROWS && $this->isConscious();
    }

    /**
     * @return bool
     */
    public function isConscious()
    {
        return $this->getGridOfFatigue()->getNumberOfFilledRows() < GridOfFatigue::UNCONSCIOUS_NUMBER_OF_ROWS;
    }

    /**
     * @param RestPower $restPower
     * @return int amount of actually rested points of fatigue
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function rest(RestPower $restPower)
    {
        $this->checkIfNeedsToRollAgainstMalusFirst();
        $previousFatigue = $this->fatigue;
        $this->fatigue = max(0, $this->fatigue - $restPower->getRestUpTo());
        $restedAmount = $previousFatigue - $this->fatigue;
        $this->resolveMalusAfterRest($restedAmount);

        return $restedAmount;
    }

    /**
     * @param int $restedAmount
     */
    private function resolveMalusAfterRest($restedAmount)
    {
        if ($restedAmount === 0) { // fatigue remains the same
            return;
        }
        if ($this->maySufferFromFatigue()) {
            $this->reasonToRollAgainstFatigueMalus = ReasonToRollAgainstFatigueMalus::getRestReason();
        } else if ($this->isConscious()) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->malusFromFatigue = MalusFromFatigue::getIt(0); // pain is gone and creature feel it - lets remove the malus
        } // otherwise left the previous malus - creature will suffer by it when comes conscious again
    }

    /**
     * @return int
     */
    public function getFatigue()
    {
        return $this->fatigue;
    }

    /**
     * @return int
     */
    public function getStaminaMaximum()
    {
        return $this->getFatigueBoundaryValue() * GridOfFatigue::TOTAL_NUMBER_OF_ROWS;
    }

    /**
     * @return int
     */
    public function getRemainingStaminaAmount()
    {
        return max(0, $this->getStaminaMaximum() - $this->getFatigue());
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return GridOfFatigue
     */
    public function getGridOfFatigue()
    {
        if ($this->gridOfFatigue === null) {
            $this->gridOfFatigue = new GridOfFatigue($this);
        }

        return $this->gridOfFatigue;
    }

    /**
     * @return int
     */
    public function getFatigueBoundaryValue()
    {
        return $this->fatigueBoundaryValue;
    }

    /**
     * @param FatigueBoundary $fatigueBoundary
     */
    public function changeFatigueBoundary(FatigueBoundary $fatigueBoundary)
    {
        if ($this->getFatigueBoundaryValue() === $fatigueBoundary->getValue()) {
            return;
        }
        $previousStaminaMaximum = $this->getStaminaMaximum();
        $this->fatigueBoundaryValue = $fatigueBoundary->getValue();
        if ($previousStaminaMaximum > $this->getStaminaMaximum()) { // current fatigue relatively increases (if any)
            $this->resolveMalusAfterFatigue($previousStaminaMaximum - $this->getStaminaMaximum());
        } elseif ($previousStaminaMaximum < $this->getStaminaMaximum()) { // current fatigue relatively decreases (if any)
            $this->resolveMalusAfterRest($this->getStaminaMaximum() - $previousStaminaMaximum);
        }
    }

    /**
     * @return bool
     */
    public function isAlive()
    {
        return $this->getRemainingStaminaAmount() > 0;
    }

    /**
     * @return MalusFromFatigue
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function getMalusFromFatigue()
    {
        $this->checkIfNeedsToRollAgainstMalusFirst();
        if ($this->getGridOfFatigue()->getNumberOfFilledRows() === 0) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return MalusFromFatigue::getIt(0);
        }

        /**
         * note: Can grow only on new fatigue when reach second row in grid of fatigue.
         * Can decrease only on rest of any fatigue when on second row in grid of fatigue.
         * Is removed when first row of grid of fatigue is not filled.
         * Even unconscious can has a malus (but would be wrong if applied).
         */
        return $this->malusFromFatigue;
    }

    /**
     * @return bool
     */
    public function needsToRollAgainstMalus()
    {
        return $this->reasonToRollAgainstFatigueMalus !== null;
    }

    /**
     * @return ReasonToRollAgainstFatigueMalus|null
     */
    public function getReasonToRollAgainstFatigueMalus()
    {
        return $this->reasonToRollAgainstFatigueMalus;
    }

    /**
     * @param Will $will
     * @param Roller2d6DrdPlus $roller2d6DrdPlus
     * @return MalusFromFatigue resulted malus
     * @throws \DrdPlus\Stamina\Exceptions\UselessRollAgainstMalus
     */
    public function rollAgainstMalusFromFatigue(Will $will, Roller2d6DrdPlus $roller2d6DrdPlus)
    {
        if (!$this->needsToRollAgainstMalus()) {
            throw new Exceptions\UselessRollAgainstMalus(
                'There is no need to roll against malus from fatigue'
                . ($this->isConscious() ? '' : ' (being is unconscious)')
            );
        }
        $malusFromFatigue = $this->reasonToRollAgainstFatigueMalus->becauseOfRest()
            ? $this->rollAgainstMalusOnRest($will, $roller2d6DrdPlus)
            : $this->rollAgainstMalusOnFatigue($will, $roller2d6DrdPlus);
        $this->reasonToRollAgainstFatigueMalus = null;

        return $malusFromFatigue;
    }

    /**
     * @param Will $will
     * @param Roller2d6DrdPlus $roller2d6DrdPlus
     * @return MalusFromFatigue
     */
    private function rollAgainstMalusOnRest(Will $will, Roller2d6DrdPlus $roller2d6DrdPlus)
    {
        if ($this->malusFromFatigue->getValue() === 0) {
            return $this->malusFromFatigue; // on rest can be the malus only lowered - there is nothing to lower
        }
        $newRoll = $this->createRollOnWillAgainstMalus($will, $roller2d6DrdPlus);
        // lesser (or same of course) malus remains; can not be increased on resting
        if ($this->malusFromFatigue->getValue() >= $newRoll->getMalusValue()) { // greater in mathematical meaning (malus is negative)
            return $this->malusFromFatigue; // lesser malus remains
        }

        return $this->setMalusFromFatigue($newRoll);
    }

    /**
     * @param Will $will
     * @param Roller2d6DrdPlus $roller2d6DrdPlus
     * @return RollOnWillAgainstMalus
     */
    private function createRollOnWillAgainstMalus(Will $will, Roller2d6DrdPlus $roller2d6DrdPlus)
    {
        return new RollOnWillAgainstMalus(new RollOnWill($will, $roller2d6DrdPlus->roll()));
    }

    /**
     * @param RollOnWillAgainstMalus $rollOnWillAgainstMalus
     * @return MalusFromFatigue
     */
    private function setMalusFromFatigue(RollOnWillAgainstMalus $rollOnWillAgainstMalus)
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return $this->malusFromFatigue = MalusFromFatigue::getIt($rollOnWillAgainstMalus->getMalusValue());
    }

    /**
     * @param Will $will
     * @param Roller2d6DrdPlus $roller2d6DrdPlus
     * @return MalusFromFatigue
     */
    private function rollAgainstMalusOnFatigue(Will $will, Roller2d6DrdPlus $roller2d6DrdPlus)
    {
        if ($this->malusFromFatigue->getValue() === MalusFromFatigue::MOST) {
            return $this->malusFromFatigue;
        }
        $newRoll = $this->createRollOnWillAgainstMalus($will, $roller2d6DrdPlus);
        // bigger (or same of course) malus remains; can not be decreased on new fatigue
        if ($this->malusFromFatigue->getValue() <= $newRoll->getMalusValue() // lesser in mathematical meaning (malus is negative)
        ) {
            return $this->malusFromFatigue; // greater malus remains
        }

        return $this->setMalusFromFatigue($newRoll);
    }
}