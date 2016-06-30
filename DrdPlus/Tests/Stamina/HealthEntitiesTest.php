<?php
namespace DrdPlus\Tests\Stamina;

use Doctrineum\Tests\Entity\AbstractDoctrineEntitiesTest;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Derived\Endurance;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Stamina\EnumTypes\StaminaEnumsRegistrar;
use DrdPlus\Stamina\Stamina;
use DrdPlus\Stamina\FatigueSize;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Tables\Measurements\Fatigue\FatigueTable;
use DrdPlus\Tables\Measurements\Wounds\WoundsTable;

class HealthEntitiesTest extends AbstractDoctrineEntitiesTest
{
    protected function setUp()
    {
        parent::setUp();
        StaminaEnumsRegistrar::registerAll();
    }

    protected function getDirsWithEntities()
    {
        return [
            str_replace(DIRECTORY_SEPARATOR . 'Tests', '', __DIR__)
        ];
    }

    protected function createEntitiesToPersist()
    {
        $stamina = new Stamina(
            new FatigueBoundary(
                new Endurance(Strength::getIt(2), Will::getIt(3)),
                new FatigueTable(new WoundsTable())
            )
        );
        $stamina->addFatigue(new FatigueSize(7));

        return [$stamina];
    }

}