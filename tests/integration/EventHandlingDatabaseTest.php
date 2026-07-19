<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use QUI;
use QUI\History\EventHandling;
use QUI\Projects\Project;
use QUI\System\Console\Tools\MigrationV2;

require_once __DIR__ . '/DatabaseTestCase.php';

class EventHandlingDatabaseTest extends DatabaseTestCase
{
    public function testMigrationReplacesLegacyUserIdsInSiteAndBrickHistory(): void
    {
        $projects = QUI::getProjectManager()->getProjects(true);

        if ($projects === []) {
            self::markTestSkipped('The test installation has no configured project.');
        }

        foreach ($projects as $Project) {
            $this->createProjectTables($Project);
        }

        $Project = reset($projects);
        self::assertInstanceOf(Project::class, $Project);

        $siteTable = QUI::getDBProjectTableName('archiv', $Project);
        $brickTable = QUI::getDBProjectTableName('history_bricks', $Project);
        $this->insertEntry($siteTable, 123, '2024-01-01 10:00:00', '{}');
        $this->insertEntry($brickTable, 456, '2024-01-01 10:00:00', '{}');

        $Console = $this->createMock(MigrationV2::class);
        $Console->expects(self::exactly(2))->method('writeLn');

        EventHandling::onQuiqqerMigrationV2($Console);

        self::assertSame('known-user-uuid', $this->Connection->fetchOne(
            'SELECT uid FROM ' . $this->Connection->getDatabasePlatform()->quoteIdentifier($siteTable)
        ));
        self::assertSame('known-user-uuid', $this->Connection->fetchOne(
            'SELECT uid FROM ' . $this->Connection->getDatabasePlatform()->quoteIdentifier($brickTable)
        ));
    }

    private function createProjectTables(Project $Project): void
    {
        foreach (['archiv', 'history_bricks'] as $name) {
            $table = QUI::getDBProjectTableName($name, $Project);
            $quotedTable = $this->Connection->getDatabasePlatform()->quoteIdentifier($table);

            $this->Connection->executeStatement(
                "CREATE TABLE IF NOT EXISTS $quotedTable ("
                . 'id INTEGER NOT NULL, '
                . 'created DATETIME NOT NULL, '
                . 'data TEXT NULL, '
                . 'uid VARCHAR(50) NULL, '
                . 'PRIMARY KEY (id, created)'
                . ')'
            );
        }
    }
}
