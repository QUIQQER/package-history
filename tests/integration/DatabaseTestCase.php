<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User;
use QUI\Projects\Project;
use QUI\Users\Manager as UserManager;
use ReflectionProperty;

abstract class DatabaseTestCase extends TestCase
{
    protected Connection $Connection;
    protected Project $Project;
    protected string $siteTable;
    protected string $brickTable;

    private ?Connection $originalConnection;
    private ?UserManager $originalUserManager;

    protected function setUp(): void
    {
        parent::setUp();

        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $this->originalConnection = $ConnectionProperty->getValue();
        $this->originalUserManager = QUI::$Users;

        $this->Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);

        $ConnectionProperty->setValue(null, $this->Connection);

        $this->Project = $this->createProject();
        $this->siteTable = QUI::getDBProjectTableName('archiv', $this->Project);
        $this->brickTable = QUI::getDBProjectTableName('history_bricks', $this->Project);

        $this->createHistoryTable($this->siteTable);
        $this->createHistoryTable($this->brickTable);
        $this->installUserManager();
    }

    protected function tearDown(): void
    {
        $this->Connection->close();

        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $ConnectionProperty->setValue(null, $this->originalConnection);
        QUI::$Users = $this->originalUserManager;

        parent::tearDown();
    }

    protected function createProject(int $siteLimit = 20, int $brickLimit = 20): Project
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getName')->willReturn('historyphpunit');
        $Project->method('getLang')->willReturn('en');
        $Project->method('getConfig')->willReturnCallback(
            static fn (string $name): int => match ($name) {
                'history.limits.limitPerSite' => $siteLimit,
                'history.limits.limitPerBrick' => $brickLimit,
                default => 0
            }
        );

        return $Project;
    }

    protected function insertEntry(
        string $table,
        int $id,
        string $created,
        string $data,
        string $uid = 'known-user'
    ): void {
        $this->Connection->insert($table, [
            'id' => $id,
            'created' => $created,
            'data' => $data,
            'uid' => $uid
        ]);
    }

    private function createHistoryTable(string $table): void
    {
        $quotedTable = $this->Connection->getDatabasePlatform()->quoteIdentifier($table);

        $this->Connection->executeStatement(
            "CREATE TABLE $quotedTable ("
            . 'id INTEGER NOT NULL, '
            . 'created DATETIME NOT NULL, '
            . 'data TEXT NULL, '
            . 'uid VARCHAR(50) NULL, '
            . 'PRIMARY KEY (id, created)'
            . ')'
        );
    }

    private function installUserManager(): void
    {
        $SessionUser = $this->createMock(User::class);
        $SessionUser->method('getUUID')->willReturn('session-user');
        $SessionUser->method('getName')->willReturn('Session User');

        $KnownUser = $this->createMock(User::class);
        $KnownUser->method('getUUID')->willReturn('known-user-uuid');
        $KnownUser->method('getName')->willReturn('Known User');

        $Users = $this->createMock(UserManager::class);
        $Users->method('getUserBySession')->willReturn($SessionUser);
        $Users->method('get')->willReturnCallback(
            static function (int|string $id) use ($KnownUser): User {
                if ($id === 'known-user') {
                    return $KnownUser;
                }

                throw new QUI\Exception('Unknown test user');
            }
        );

        QUI::$Users = $Users;
    }
}
