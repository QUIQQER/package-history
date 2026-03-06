<?php

namespace QUITests\History;

use DateTime;
use PHPUnit\Framework\TestCase;
use QUI\Bricks\Brick;
use QUI\History\Brick as HistoryBrick;

class BrickUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        TestableBrick::resetEntries();
    }

    public function testProjectTableNameConstant(): void
    {
        $this->assertSame('history_bricks', HistoryBrick::PROJECT_TABLE_NAME);
    }

    public function testGenerateDifferenceUsesHistoryEntries(): void
    {
        $date1 = new DateTime('2024-01-01 10:00:00');
        $date2 = new DateTime('2024-01-01 11:00:00');

        TestableBrick::setEntry($date1, '<p>old text</p>');
        TestableBrick::setEntry($date2, '<p>new text</p>');

        $Brick = $this->createMock(Brick::class);
        $diff = TestableBrick::generateDifference($Brick, $date1, $date2);

        $this->assertNotSame('', trim($diff));
        $this->assertStringContainsString('text', $diff);
        $this->assertTrue(
            str_contains($diff, '<ins') || str_contains($diff, '<del'),
            'Diff output should contain insertion/deletion tags'
        );
    }
}

class TestableBrick extends HistoryBrick
{
    /** @var array<string, array<string, mixed>> */
    private static array $entries = [];

    public static function setEntry(DateTime $Date, string $content): void
    {
        self::$entries[$Date->format('Y-m-d H:i:s')] = ['content' => $content];
    }

    public static function resetEntries(): void
    {
        self::$entries = [];
    }

    public static function getHistoryEntryData(Brick $Brick, DateTime $Date): array
    {
        $key = $Date->format('Y-m-d H:i:s');

        return self::$entries[$key] ?? ['content' => ''];
    }
}
