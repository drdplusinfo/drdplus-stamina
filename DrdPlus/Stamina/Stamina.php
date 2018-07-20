<?php
declare(strict_types = 1);

namespace DrdPlus\Stamina;

use Doctrineum\Entity\Entity;
use DrdPlus\DiceRolls\Templates\Rolls\Roll2d6DrdPlus;
use DrdPlus\Properties\Derived\Endurance;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Properties\Base\Will;
use DrdPlus\RollsOn\Traps\RollOnWillAgainstMalus;
use DrdPlus\RollsOn\Traps\RollOnWill;
use DrdPlus\Tables\Measurements\Wounds\WoundsBonus;
use DrdPlus\Tables\Tables;
use Granam\Strict\Object\StrictObject;

/**
 * @Doctrine\ORM\Mapping\Entity
 */
class Stamina extends StrictObject implements Entity
{
    /**
     * @Doctrine\ORM\Mapping\Id
     * @Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     * @Doctrine\ORM\Mapping\Column(type="integer")
     */
    private $id;
    /**
     * @var Fatigue
     * @Doctrine\ORM\Mapping\Column(type="fatigue")
     */
    private $fatigue;
    /**
     * @var MalusFromFatigue
     * @Doctrine\ORM\Mapping\Column(type="malus_from_fatigue")
     */
    private $malusFromFatigue;
    /**
     * @var ReasonToRollAgainstMalusFromFatigue|null
     * @Doctrine\ORM\Mapping\Column(type="reason_to_roll_against_fatigue_malus", nullable=true)
     */
    private $reasonToRollAgainstMalusFromFatigue;
    /**
     * @var GridOfFatigue|null is just a helper, does not need to be persisted
     */
    private $gridOfFatigue;

    public function __construct()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->fatigue = Fatigue::getIt(0);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->malusFromFatigue = MalusFromFatigue::getIt(0);
    }

    /**
     * @param Fatigue $fatigue
     * @param FatigueBoundary $fatigueBoundary
     * @return Fatigue summarized fatigue
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function addFatigue(Fatigue $fatigue, FatigueBoundary $fatigueBoundary): Fatigue
    {
        $this->checkIfNeedsToRollAgainstMalusFirst();
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->fatigue = Fatigue::getIt($this->fatigue->getValue() + $fatigue->getValue());
        $this->resolveMalusAfterFatigue($this->fatigue->getValue(), $fatigueBoundary);

        return $this->fatigue;
    }

    /**
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    private function checkIfNeedsToRollAgainstMalusFirst()
    {
        if ($this->needsToRollAgainstMalusFromFatigue()) {
            throw new Exceptions\NeedsToRollAgainstMalusFirst('Need to roll on will against malus caused by fatigue');
        }
    }

    /**
     * @param int $fatigueAmount
     * @param FatigueBoundary $fatigueBoundary
     */
    private function resolveMalusAfterFatigue($fatigueAmount, FatigueBoundary $fatigueBoundary)
    {
        if ($fatigueAmount === 0) {
            return;
        }
        if ($this->maySufferFromFatigue($fatigueBoundary)) {
            $this->reasonToRollAgainstMalusFromFatigue = ReasonToRollAgainstMalusFromFatigue::getFatigueReason();
        } elseif ($this->isConscious($fatigueBoundary)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->malusFromFatigue = MalusFromFatigue::getIt(0);
        } // otherwise left the previous malus - being will suffer by it when comes conscious again
    }

    /**
     * @param FatigueBoundary $fatigueBoundary
     * @return bool
     */
    public function maySufferFromFatigue(FatigueBoundary $fatigueBoundary): bool
    {
        // if the being became unconscious than the roll against pain malus is not re-rolled
        return $this->mayHaveMalusFromFatigue($fatigueBoundary) && $this->isConscious($fatigueBoundary);
    }

    /**
     * @param FatigueBoundary $fatigueBoundary
     * @return bool
     */
    public function isConscious(FatigueBoundary $fatigueBoundary): bool
    {
        return $this->getGridOfFatigue()->getNumberOfFilledRows($fatigueBoundary) < GridOfFatigue::UNCONSCIOUS_NUMBER_OF_ROWS;
    }

    /**
     * @param RestPower $restPower
     * @param FatigueBoundary $fatigueBoundary
     * @param Endurance $endurance
     * @param Tables $tables
     * @return int amount of actually rested points of fatigue
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function rest(RestPower $restPower, FatigueBoundary $fatigueBoundary, Endurance $endurance, Tables $tables): int
    {
        $this->checkIfNeedsToRollAgainstMalusFirst();
        $previousFatigue = $this->fatigue->getValue();
        $restUpTo = $tables->getWoundsTable()->toWounds(
            new WoundsBonus($restPower->getValue() + $endurance->getValue(), $tables->getWoundsTable())
        );
        $remainingFatigue = $this->fatigue->getValue() - $restUpTo->getValue();
        if ($remainingFatigue < 0) {
            $remainingFatigue = 0;
        }
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $this->fatigue = Fatigue::getIt($remainingFatigue);
        $restedAmount = $previousFatigue - $this->fatigue->getValue();
        $this->resolveMalusAfterRest($restedAmount, $fatigueBoundary);

        return $restedAmount;
    }

    /**
     * @param int $restedAmount
     * @param FatigueBoundary $fatigueBoundary
     */
    private function resolveMalusAfterRest($restedAmount, FatigueBoundary $fatigueBoundary)
    {
        if ($restedAmount === 0) { // fatigue remains the same
            return;
        }
        if ($this->maySufferFromFatigue($fatigueBoundary)) {
            $this->reasonToRollAgainstMalusFromFatigue = ReasonToRollAgainstMalusFromFatigue::getRestReason();
        } elseif ($this->isConscious($fatigueBoundary)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->malusFromFatigue = MalusFromFatigue::getIt(0); // pain is gone and being feel it - lets remove the malus
        } // otherwise left the previous malus - being will suffer by it when comes conscious again
    }

    /**
     * @return Fatigue
     */
    public function getFatigue(): Fatigue
    {
        return $this->fatigue;
    }

    /**
     * @param FatigueBoundary $fatigueBoundary
     * @return int
     */
    public function getStaminaMaximum(FatigueBoundary $fatigueBoundary): int
    {
        return $fatigueBoundary->getValue() * GridOfFatigue::TOTAL_NUMBER_OF_ROWS;
    }

    /**
     * @param FatigueBoundary $fatigueBoundary
     * @return int
     */
    public function getRemainingStaminaAmount(FatigueBoundary $fatigueBoundary): int
    {
        return max(0, $this->getStaminaMaximum($fatigueBoundary) - $this->getFatigue()->getValue());
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return GridOfFatigue
     */
    public function getGridOfFatigue(): GridOfFatigue
    {
        if ($this->gridOfFatigue === null) {
            $this->gridOfFatigue = new GridOfFatigue($this);
        }

        return $this->gridOfFatigue;
    }

    /**
     * @param FatigueBoundary $fatigueBoundary
     * @return bool
     */
    public function isAlive(FatigueBoundary $fatigueBoundary): bool
    {
        return $this->getRemainingStaminaAmount($fatigueBoundary) > 0;
    }

    /**
     * @param FatigueBoundary $fatigueBoundary
     * @return MalusFromFatigue
     * @throws \DrdPlus\Stamina\Exceptions\NeedsToRollAgainstMalusFirst
     */
    public function getMalusFromFatigue(FatigueBoundary $fatigueBoundary): MalusFromFatigue
    {
        $this->checkIfNeedsToRollAgainstMalusFirst();
        if (!$this->mayHaveMalusFromFatigue($fatigueBoundary)) {
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
     * @param FatigueBoundary $fatigueBoundary
     * @return bool
     */
    public function mayHaveMalusFromFatigue(FatigueBoundary $fatigueBoundary): bool
    {
        return $this->getGridOfFatigue()->getNumberOfFilledRows($fatigueBoundary) >= GridOfFatigue::PAIN_NUMBER_OF_ROWS;
    }

    /**
     * @return bool
     */
    public function needsToRollAgainstMalusFromFatigue(): bool
    {
        return $this->reasonToRollAgainstMalusFromFatigue !== null;
    }

    /**
     * @return ReasonToRollAgainstMalusFromFatigue|null
     */
    public function getReasonToRollAgainstMalusFromFatigue(): ?ReasonToRollAgainstMalusFromFatigue
    {
        return $this->reasonToRollAgainstMalusFromFatigue;
    }

    /**
     * @param Will $will
     * @param Roll2d6DrdPlus $roll2D6DrdPlus
     * @param FatigueBoundary $fatigueBoundary
     * @return MalusFromFatigue resulted malus
     * @throws \DrdPlus\Stamina\Exceptions\UselessRollAgainstMalus
     */
    public function rollAgainstMalusFromFatigue(
        Will $will,
        Roll2d6DrdPlus $roll2D6DrdPlus,
        FatigueBoundary $fatigueBoundary
    ): MalusFromFatigue
    {
        if (!$this->needsToRollAgainstMalusFromFatigue()) {
            throw new Exceptions\UselessRollAgainstMalus(
                'There is no need to roll against malus from fatigue'
                . ($this->isConscious($fatigueBoundary) ? '' : ' (being is unconscious)')
            );
        }
        $malusFromFatigue = $this->reasonToRollAgainstMalusFromFatigue->becauseOfRest()
            ? $this->rollAgainstMalusOnRest($will, $roll2D6DrdPlus)
            : $this->rollAgainstMalusOnFatigue($will, $roll2D6DrdPlus);
        $this->reasonToRollAgainstMalusFromFatigue = null;

        return $malusFromFatigue;
    }

    /**
     * @param Will $will
     * @param Roll2d6DrdPlus $roll2d6DrdPlus
     * @return MalusFromFatigue
     */
    private function rollAgainstMalusOnRest(Will $will, Roll2d6DrdPlus $roll2d6DrdPlus): MalusFromFatigue
    {
        if ($this->malusFromFatigue->getValue() === 0) {
            return $this->malusFromFatigue; // on rest can be the malus only lowered - there is nothing to lower
        }
        $newRoll = $this->createRollOnWillAgainstMalus($will, $roll2d6DrdPlus);
        // lesser (or same of course) malus remains; can not be increased on resting
        if ($this->malusFromFatigue->getValue() >= $newRoll->getMalusValue()) { // greater in mathematical meaning (malus is negative)
            return $this->malusFromFatigue; // lesser malus remains
        }

        return $this->setMalusFromFatigue($newRoll);
    }

    /**
     * @param Will $will
     * @param Roll2d6DrdPlus $roll2d6DrdPlus
     * @return RollOnWillAgainstMalus
     */
    private function createRollOnWillAgainstMalus(Will $will, Roll2d6DrdPlus $roll2d6DrdPlus): RollOnWillAgainstMalus
    {
        return new RollOnWillAgainstMalus(new RollOnWill($will, $roll2d6DrdPlus));
    }

    /**
     * @param RollOnWillAgainstMalus $rollOnWillAgainstMalus
     * @return MalusFromFatigue
     */
    private function setMalusFromFatigue(RollOnWillAgainstMalus $rollOnWillAgainstMalus): MalusFromFatigue
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        return $this->malusFromFatigue = MalusFromFatigue::getIt($rollOnWillAgainstMalus->getMalusValue());
    }

    /**
     * @param Will $will
     * @param Roll2d6DrdPlus $roll2d6DrdPlus
     * @return MalusFromFatigue
     */
    private function rollAgainstMalusOnFatigue(Will $will, Roll2d6DrdPlus $roll2d6DrdPlus): MalusFromFatigue
    {
        if ($this->malusFromFatigue->getValue() === MalusFromFatigue::MOST) {
            return $this->malusFromFatigue;
        }
        $newRoll = $this->createRollOnWillAgainstMalus($will, $roll2d6DrdPlus);
        // bigger (or same of course) malus remains; can not be decreased on new fatigue
        if ($this->malusFromFatigue->getValue() <= $newRoll->getMalusValue() // lesser in mathematical meaning (malus is negative)
        ) {
            return $this->malusFromFatigue; // greater malus remains
        }

        return $this->setMalusFromFatigue($newRoll);
    }
}