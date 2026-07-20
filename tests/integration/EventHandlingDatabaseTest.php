<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use Doctrine\DBAL\Types\StringType;
use QUI;
use QUI\History\EventHandling;
use QUI\Package\Package;
use QUI\Projects\Project;
use QUI\System\Console\Tools\MigrationV2;

require_once __DIR__ . '/DatabaseTestCase.php';

class EventHandlingDatabaseTest extends DatabaseTestCase
{
    public function testPackageSetupMigratesLegacyIntegerUidColumns(): void
    {
        $projects = QUI::getProjectManager()->getProjects(true);

        if ($projects === []) {
            self::markTestSkipped('The test installation has no configured project.');
        }

        $Project = reset($projects);
        self::assertInstanceOf(Project::class, $Project);

        foreach (['archiv', 'history_bricks'] as $suffix) {
            $this->createProjectTable($Project, $suffix, 'INTEGER');
            $this->insertEntry(
                QUI::getDBProjectTableName($suffix, $Project),
                123,
                '2024-01-01 10:00:00',
                '{}',
                '42'
            );
        }

        $Package = $this->createMock(Package::class);
        $Package->method('getName')->willReturn('quiqqer/history');

        EventHandling::onPackageSetup($Package);

        foreach (['archiv', 'history_bricks'] as $suffix) {
            $table = QUI::getDBProjectTableName($suffix, $Project);
            $UidColumn = QUI::getSchemaManager()->introspectTable($table)->getColumn('uid');

            self::assertInstanceOf(StringType::class, $UidColumn->getType());
            self::assertSame(50, $UidColumn->getLength());
            self::assertSame('42', (string)$this->Connection->fetchOne(
                'SELECT uid FROM ' . $this->Connection->getDatabasePlatform()->quoteIdentifier($table)
            ));
        }
    }

    public function testMigrationReplacesLegacyUserIdsInSiteAndBrickHistory(): void
    {
        $projects = QUI::getProjectManager()->getProjects(true);

        if ($projects === []) {
            self::markTestSkipped('The test installation has no configured project.');
        }

        foreach ($projects as $Project) {
            $this->createProjectTables($Project, 'INTEGER');
        }

        $Project = reset($projects);
        self::assertInstanceOf(Project::class, $Project);

        $siteTable = QUI::getDBProjectTableName('archiv', $Project);
        $brickTable = QUI::getDBProjectTableName('history_bricks', $Project);
        $this->insertEntry($siteTable, 123, '2024-01-01 10:00:00', '{}', '42');
        $this->insertEntry($brickTable, 456, '2024-01-01 10:00:00', '{}', '42');

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

    private function createProjectTables(Project $Project, string $uidType): void
    {
        foreach (['archiv', 'history_bricks'] as $name) {
            $this->createProjectTable($Project, $name, $uidType);
        }
    }

    private function createProjectTable(Project $Project, string $name, string $uidType): void
    {
        $table = QUI::getDBProjectTableName($name, $Project);
        $quotedTable = $this->Connection->getDatabasePlatform()->quoteIdentifier($table);

        $this->Connection->executeStatement("DROP TABLE IF EXISTS $quotedTable");
        $this->Connection->executeStatement(
            "CREATE TABLE $quotedTable ("
            . 'id INTEGER NOT NULL, '
            . 'created DATETIME NOT NULL, '
            . 'data TEXT NULL, '
            . "uid $uidType NULL, "
            . 'PRIMARY KEY (id, created)'
            . ')'
        );
    }
}
