<?php

namespace QUITests\History\Console;

require_once __DIR__ . '/TestableInitialize.php';

use PHPUnit\Framework\TestCase;
use QUI\History\Console\Initialize;
use QUI\Projects\Project;

class InitializeTest extends TestCase
{
    public function testConstructorSetsCommandName(): void
    {
        $Tool = new TestableInitialize();

        $this->assertSame('history:initialize', $Tool->getName());
    }

    public function testProcessSitesReturnsEarlyForEmptySiteList(): void
    {
        $Tool = new TestableInitialize();
        $Project = $this->createMock(Project::class);
        $Project->method('getSitesIds')->willReturn([]);

        $Tool->processSitesPublic($Project);

        $this->assertTrue(true);
    }
}
