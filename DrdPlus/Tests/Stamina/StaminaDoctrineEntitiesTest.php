<?php
namespace DrdPlus\Tests\Stamina;

use Doctrineum\Tests\Entity\AbstractDoctrineEntitiesTest;
use DrdPlus\Properties\Base\Will;
use DrdPlus\Properties\Derived\Endurance;
use DrdPlus\Properties\Derived\FatigueBoundary;
use DrdPlus\Stamina\EnumTypes\StaminaEnumsRegistrar;
use DrdPlus\Stamina\Fatigue;
use DrdPlus\Stamina\Stamina;
use DrdPlus\Properties\Base\Strength;
use DrdPlus\Tables\Tables;

class StaminaDoctrineEntitiesTest extends AbstractDoctrineEntitiesTest
{
    protected function setUp()
    {
        parent::setUp();
        StaminaEnumsRegistrar::registerAll();
    }

    protected function getDirsWithEntities()
    {
        return [
            str_replace(DIRECTORY_SEPARATOR . 'Tests', '', __DIR__),
        ];
    }

    protected function createEntitiesToPersist()
    {
        $stamina = new Stamina(
            $fatigueBoundary = new FatigueBoundary(new Endurance(Strength::getIt(2), Will::getIt(3)), Tables::getIt())
        );
        $stamina->addFatigue(Fatigue::getIt(7), $fatigueBoundary);

        return [$stamina];
    }

}