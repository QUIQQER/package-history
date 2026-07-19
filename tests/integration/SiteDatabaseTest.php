<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use DateTime;
use QUI;
use QUI\Cache\Manager as CacheManager;
use QUI\History\Site as SiteHistory;
use QUI\Interfaces\Projects\Site;

require_once __DIR__ . '/DatabaseTestCase.php';

class SiteDatabaseTest extends DatabaseTestCase
{
    private const SITE_ID = 987654;

    public function testReturnsEntriesNewestFirstAndResolvesAvailableUsers(): void
    {
        $this->insertEntry(
            $this->siteTable,
            self::SITE_ID,
            '2024-01-01 10:00:00',
            '{"title":"old"}',
            'missing-user'
        );
        $this->insertEntry(
            $this->siteTable,
            self::SITE_ID,
            '2024-01-01 11:00:00',
            '{"title":"new"}'
        );

        $list = SiteHistory::getList($this->createSite());

        self::assertSame(['2024-01-01 11:00:00', '2024-01-01 10:00:00'], array_column($list, 'created'));
        self::assertSame(['Known User', ''], array_column($list, 'username'));
    }

    public function testReturnsDecodedHistoryDataAndHandlesInvalidJson(): void
    {
        $this->insertEntry(
            $this->siteTable,
            self::SITE_ID,
            '2024-02-01 10:00:00',
            '{"title":"Version 1","active":true}'
        );
        $this->insertEntry(
            $this->siteTable,
            self::SITE_ID,
            '2024-02-01 11:00:00',
            'invalid json'
        );

        self::assertSame(
            ['title' => 'Version 1', 'active' => true],
            SiteHistory::getHistoryEntry($this->createSite(), new DateTime('2024-02-01 10:00:00'))
        );
        self::assertSame(
            [],
            SiteHistory::getHistoryEntry($this->createSite(), new DateTime('2024-02-01 11:00:00'))
        );
    }

    public function testMissingHistoryEntryThrows(): void
    {
        $this->expectException(QUI\Exception::class);
        $this->expectExceptionMessage('History entry not exist');

        SiteHistory::getHistoryEntry($this->createSite(), new DateTime('2024-03-01 10:00:00'));
    }

    public function testOnSaveCreatesEntryAndRemovesOverflow(): void
    {
        $this->Project = $this->createProject(siteLimit: 1);
        $this->insertEntry(
            $this->siteTable,
            self::SITE_ID,
            '2020-01-01 10:00:00',
            '{"title":"outdated"}'
        );

        $cacheKey = $this->cacheKey();
        CacheManager::clear($cacheKey);

        try {
            SiteHistory::onSave($this->createSite(['title' => 'Current title']));
        } finally {
            CacheManager::clear($cacheKey);
        }

        $rows = $this->Connection->fetchAllAssociative(
            'SELECT data FROM ' . $this->Connection->getDatabasePlatform()->quoteIdentifier($this->siteTable)
        );

        self::assertCount(1, $rows);
        self::assertSame(['title' => 'Current title'], json_decode($rows[0]['data'], true));
    }

    /** @param array<string, mixed> $attributes */
    private function createSite(array $attributes = []): Site
    {
        $Site = $this->createMock(Site::class);
        $Site->method('getProject')->willReturn($this->Project);
        $Site->method('getId')->willReturn(self::SITE_ID);
        $Site->method('getAttributes')->willReturn($attributes);

        return $Site;
    }

    private function cacheKey(): string
    {
        return $this->Project->getName() . '_' . $this->Project->getLang() . '_' . self::SITE_ID;
    }
}
