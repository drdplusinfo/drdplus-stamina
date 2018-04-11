<?php
namespace DrdPlus\Tests\Stamina\EnumTypes;

use Doctrine\DBAL\Types\Type;
use Doctrineum\DateInterval\DBAL\Types\DateIntervalType;
use DrdPlus\Stamina\EnumTypes\StaminaEnumsRegistrar;
use Granam\String\StringTools;
use PHPUnit\Framework\TestCase;

class StaminaEnumsRegistrarTest extends TestCase
{
    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     */
    public function I_can_register_all_enums_at_once(): void
    {
        StaminaEnumsRegistrar::registerAll();

        self::assertTrue(
            Type::hasType(DateIntervalType::DATE_INTERVAL),
            'Type ' . DateIntervalType::DATE_INTERVAL . ' not registered'
        );

        foreach ($this->getLocalEnumTypeClasses(__DIR__ . '/../../../Stamina') as $enumTypeClass) {
            $expectedEnumTypeName = preg_replace('~_type$~', '', StringTools::camelCaseToSnakeCasedBasename($enumTypeClass));
            self::assertTrue(
                Type::hasType($expectedEnumTypeName),
                "Type {$expectedEnumTypeName} not registered by class {$enumTypeClass}"
            );
        }
    }

    /**
     * @param $dirToScan
     * @return array|string[]
     */
    private function getLocalEnumTypeClasses($dirToScan)
    {
        if (basename($dirToScan) === 'EnumTypes' && is_dir($dirToScan)) {
            return array_filter(
                array_map(
                    function ($folder) use ($dirToScan) {
                        $fileContent = file_get_contents($dirToScan . DIRECTORY_SEPARATOR . $folder);
                        preg_match('~^\s*namespace\s+(?<namespace>(?:\w+)(?:[\\\]\w+)*)\s*;\s*$~m', $fileContent, $matches);
                        $namespace = $matches['namespace'];
                        preg_match('~^\s*class\s+(?<class>\w+)~m', $fileContent, $matches);
                        $classBasename = $matches['class'];

                        return $namespace . '\\' . $classBasename;
                    },
                    $this->removeCurrentAndParentDir(\scandir($dirToScan, SCANDIR_SORT_NONE))
                ),
                function($class) {
                    return is_a($class, Type::class, true);
                }
            );
        }

        $enumTypes = [];
        foreach ($this->removeCurrentAndParentDir(\scandir($dirToScan, SCANDIR_SORT_NONE)) as $folder) {
            if (is_dir($dirToScan . '/' . $folder)) {
                foreach ($this->getLocalEnumTypeClasses($dirToScan . DIRECTORY_SEPARATOR . $folder) as $enumType) {
                    $enumTypes[] = $enumType;
                }
            }
        }

        return $enumTypes;
    }

    private function removeCurrentAndParentDir(array $folders)
    {
        return array_filter($folders, function ($folder) {
            return $folder !== '.' && $folder !== '..';
        });
    }
}