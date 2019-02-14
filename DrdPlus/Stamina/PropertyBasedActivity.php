<?php
declare(strict_types=1);

namespace DrdPlus\Stamina;

interface PropertyBasedActivity
{
    public function usesStrength(): bool;

    public function usesAgility(): bool;

    public function usesKnack(): bool;

    public function usesWill(): bool;

    public function usesIntelligence(): bool;

    public function usesCharisma(): bool;
}