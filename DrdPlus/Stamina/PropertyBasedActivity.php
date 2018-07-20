<?php
declare(strict_types = 1);

namespace DrdPlus\Stamina;

interface PropertyBasedActivity
{
    /**
     * @return bool
     */
    public function usesStrength();

    /**
     * @return bool
     */
    public function usesAgility();

    /**
     * @return bool
     */
    public function usesKnack();

    /**
     * @return bool
     */
    public function usesWill();

    /**
     * @return bool
     */
    public function usesIntelligence();

    /**
     * @return bool
     */
    public function usesCharisma();
}