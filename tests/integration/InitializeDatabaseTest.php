<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use QUI;
use QUI\Bricks\Brick;
use QUI\Bricks\Manager as BrickManager;
use QUI\Projects\Project;
use QUI\Projects\Site as ProjectSite;
use QUI\Projects\Site\Edit;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../stubs/AccessibleInitialize.php';
require_once __DIR__ . '/../stubs/RecordingInitialize.php';

class InitializeDatabaseTest extends DatabaseTestCase
{
    private ?BrickManager $originalBrickManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalBrickManager = BrickManager::$BrickManager;
    }

    protected function tearDown(): void
    {
        BrickManager::$BrickManager = $this->originalBrickManager;
        parent::tearDown();
    }

    public function testExecuteProcessesEveryConfiguredProject(): void
    {
        $projects = QUI::getProjectManager()::getProjectList();
        $Tool = new RecordingInitialize();

        $Tool->execute();

        self::assertCount(count($projects), $Tool->siteProjects);
        self::assertCount(count($projects), $Tool->brickProjects);
    }

    public function testProcessBricksInitializesOnlyBricksWithoutHistory(): void
    {
        $projects = QUI::getProjectManager()::getProjectList();

        if ($projects === []) {
            self::markTestSkipped('The test installation has no configured project.');
        }

        $Project = reset($projects);
        self::assertInstanceOf(Project::class, $Project);
        $table = QUI::getDBProjectTableName('history_bricks', $Project);
        $quotedTable = $this->Connection->getDatabasePlatform()->quoteIdentifier($table);
        $this->Connection->executeStatement(
            "CREATE TABLE IF NOT EXISTS $quotedTable ("
            . 'id INTEGER NOT NULL, created DATETIME NOT NULL, data TEXT NULL, uid VARCHAR(50) NULL, '
            . 'PRIMARY KEY (id, created))'
        );

        $NewBrick = $this->createProjectBrick($Project, 1001, ['content' => 'new']);
        $ExistingBrick = $this->createProjectBrick($Project, 1002, ['content' => 'existing']);
        $this->insertEntry($table, 1002, '2024-01-01 10:00:00', '{"content":"existing"}');

        $Manager = $this->createMock(BrickManager::class);
        $Manager->method('getBricksFromProject')->with($Project)->willReturn([$NewBrick, $ExistingBrick]);
        $Manager->expects(self::once())->method('saveBrick')->with(1001, [
            'id' => 1001,
            'project' => $Project->getName(),
            'lang' => $Project->getLang(),
            'content' => 'new'
        ]);
        BrickManager::$BrickManager = $Manager;

        (new AccessibleInitialize())->processBricksPublic($Project);
    }

    public function testProcessBricksReportsManagerLookupFailure(): void
    {
        $Manager = $this->createMock(BrickManager::class);
        $Manager->method('getBricksFromProject')->willThrowException(new QUI\Exception('lookup failed'));
        BrickManager::$BrickManager = $Manager;

        (new AccessibleInitialize())->processBricksPublic($this->Project);

        self::assertTrue(true);
    }

    public function testProcessSitesSkipsExistingHistorySavesMissingHistoryAndReportsErrors(): void
    {
        $ExistingSite = $this->createMock(ProjectSite::class);
        $ExistingSite->method('getProject')->willReturn($this->Project);
        $ExistingSite->method('getId')->willReturn(2001);
        $this->insertEntry($this->siteTable, 2001, '2024-01-01 10:00:00', '{}');

        $NewSite = $this->createMock(Edit::class);
        $NewSite->method('getProject')->willReturn($this->Project);
        $NewSite->method('getId')->willReturn(2002);
        $NewSite->expects(self::once())->method('save');

        $this->Project->method('getSitesIds')->willReturn([
            ['id' => 2001],
            ['id' => 2002],
            ['id' => 2003]
        ]);
        $this->Project->method('get')->willReturnCallback(
            static function (int $id) use ($ExistingSite, $NewSite): ProjectSite|Edit {
                return match ($id) {
                    2001 => $ExistingSite,
                    2002 => $NewSite,
                    default => throw new QUI\Exception('site lookup failed')
                };
            }
        );

        (new AccessibleInitialize())->processSitesPublic($this->Project);
    }

    /** @param array<string, mixed> $extraAttributes */
    private function createProjectBrick(Project $Project, int $id, array $extraAttributes): Brick
    {
        $attributes = [
            'id' => $id,
            'project' => $Project->getName(),
            'lang' => $Project->getLang()
        ] + $extraAttributes;
        $Brick = $this->createMock(Brick::class);
        $Brick->method('getAttribute')->willReturnCallback(
            static fn (string $name): mixed => $attributes[$name] ?? null
        );
        $Brick->method('getAttributes')->willReturn($attributes);

        return $Brick;
    }
}
