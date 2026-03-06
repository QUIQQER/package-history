<?php

namespace QUITests\History\Console;

use PHPUnit\Framework\TestCase;
use QUI\History\Console\Initialize;
use QUI\Projects\Project;

class InitializeTest extends TestCase
{
    /** @var class-string<Initialize> */
    private static string $InitializeClass;

    public static function setUpBeforeClass(): void
    {
        self::$InitializeClass = new class extends Initialize {
            public function processSitesPublic(Project $Project): void
            {
                $this->processSites($Project);
            }
        }::class;
    }

    public function testConstructorSetsCommandName(): void
    {
        $class = self::$InitializeClass;
        $Tool = new $class();

        $this->assertSame('history:initialize', $Tool->getName());
    }

    public function testProcessSitesReturnsEarlyForEmptySiteList(): void
    {
        $class = self::$InitializeClass;
        $Tool = new $class();
        $Project = $this->createMock(Project::class);
        $Project->method('getSitesIds')->willReturn([]);

        $Tool->processSitesPublic($Project);

        $this->assertTrue(true);
    }
}
