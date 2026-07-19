<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use DateTime;
use QUI\Bricks\Brick;
use QUI\Bricks\Manager as BrickManager;
use QUI\Cache\Manager as CacheManager;
use QUI\History\Exception\HistoryEntryNotFoundException;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../stubs/TestableBrickHistory.php';

class BrickDatabaseTest extends DatabaseTestCase
{
    private const BRICK_ID = 456789;

    private ?BrickManager $originalBrickManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalBrickManager = BrickManager::$BrickManager;
        TestableBrickHistory::$Project = $this->Project;
    }

    protected function tearDown(): void
    {
        BrickManager::$BrickManager = $this->originalBrickManager;
        CacheManager::clear('history_bricks_last_save_' . self::BRICK_ID);
        parent::tearDown();
    }

    public function testOnSaveCreatesHistoryEntry(): void
    {
        $Brick = $this->createBrick(['content' => 'saved by event']);
        $Manager = $this->createMock(BrickManager::class);
        $Manager->method('getBrickById')->with(self::BRICK_ID)->willReturn($Brick);
        BrickManager::$BrickManager = $Manager;
        CacheManager::clear('history_bricks_last_save_' . self::BRICK_ID);

        TestableBrickHistory::onSave(self::BRICK_ID);

        self::assertSame(1, (int)$this->Connection->fetchOne(
            'SELECT COUNT(*) FROM ' . $this->Connection->getDatabasePlatform()->quoteIdentifier($this->brickTable)
        ));
    }

    public function testCreatesAndReadsHistoryEntry(): void
    {
        self::assertTrue(TestableBrickHistory::createHistoryEntry($this->createBrick([
            'content' => '<p>Current brick</p>'
        ])));

        $created = (string)$this->Connection->fetchOne(
            'SELECT created FROM ' . $this->Connection->getDatabasePlatform()->quoteIdentifier($this->brickTable)
        );

        self::assertSame(
            ['id' => self::BRICK_ID, 'content' => '<p>Current brick</p>'],
            TestableBrickHistory::getHistoryEntryData($this->createBrick(), new DateTime($created))
        );
    }

    public function testDuplicateEntryReturnsFalse(): void
    {
        $Brick = $this->createBrick(['content' => 'duplicate']);

        self::assertTrue(TestableBrickHistory::createHistoryEntry($Brick));
        self::assertFalse(TestableBrickHistory::createHistoryEntry($Brick));
    }

    public function testLimitRemovesOldestEntries(): void
    {
        $this->Project = $this->createProject(brickLimit: 1);
        TestableBrickHistory::$Project = $this->Project;
        $this->insertEntry(
            $this->brickTable,
            self::BRICK_ID,
            '2020-01-01 10:00:00',
            '{"content":"old"}'
        );

        self::assertTrue(TestableBrickHistory::createHistoryEntry($this->createBrick([
            'content' => 'new'
        ])));

        $rows = $this->Connection->fetchFirstColumn(
            'SELECT data FROM ' . $this->Connection->getDatabasePlatform()->quoteIdentifier($this->brickTable)
        );

        self::assertCount(1, $rows);
        self::assertSame(['id' => self::BRICK_ID, 'content' => 'new'], json_decode($rows[0], true));
    }

    public function testReturnsEntriesNewestFirstAndUsesFallbackUsername(): void
    {
        $this->insertEntry(
            $this->brickTable,
            self::BRICK_ID,
            '2024-01-01 10:00:00',
            '{"content":"old"}',
            'missing-user'
        );
        $this->insertEntry(
            $this->brickTable,
            self::BRICK_ID,
            '2024-01-01 11:00:00',
            '{"content":"new"}'
        );

        $entries = TestableBrickHistory::getHistoryEntries($this->createBrick());

        self::assertSame(['2024-01-01 11:00:00', '2024-01-01 10:00:00'], array_column($entries, 'created'));
        self::assertSame(['Known User', '?'], array_column($entries, 'username'));
    }

    public function testInvalidAndMissingHistoryData(): void
    {
        $this->insertEntry(
            $this->brickTable,
            self::BRICK_ID + 1,
            '2024-02-01 10:00:00',
            '{"content":"different brick"}'
        );
        $this->insertEntry(
            $this->brickTable,
            self::BRICK_ID,
            '2024-02-01 10:00:00',
            'invalid json'
        );

        self::assertSame(
            [],
            TestableBrickHistory::getHistoryEntryData(
                $this->createBrick(),
                new DateTime('2024-02-01 10:00:00')
            )
        );

        $this->expectException(HistoryEntryNotFoundException::class);
        TestableBrickHistory::getHistoryEntryData(
            $this->createBrick(),
            new DateTime('2024-02-01 11:00:00')
        );
    }

    public function testRestorePassesStoredAttributesToBrickManager(): void
    {
        $this->insertEntry(
            $this->brickTable,
            self::BRICK_ID,
            '2024-04-01 10:00:00',
            '{"content":"restored"}'
        );
        $Manager = $this->createMock(BrickManager::class);
        $Manager->expects(self::once())->method('saveBrick')->with(
            self::BRICK_ID,
            ['content' => 'restored']
        );
        BrickManager::$BrickManager = $Manager;

        TestableBrickHistory::restore(
            $this->createBrick(),
            new DateTime('2024-04-01 10:00:00')
        );
    }

    /** @param array<string, mixed> $attributes */
    private function createBrick(array $attributes = []): Brick
    {
        $attributes = ['id' => self::BRICK_ID] + $attributes;
        $Brick = $this->createMock(Brick::class);
        $Brick->method('getAttribute')->willReturnCallback(
            static fn (string $name): mixed => $attributes[$name] ?? null
        );
        $Brick->method('getAttributes')->willReturn($attributes);

        return $Brick;
    }
}
