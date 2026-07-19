<?php

namespace QUI\History;

use DateTime;
use Doctrine\DBAL\Exception as DbalException;
use PCSG\PhpHtmlDiff\HtmlDiff;
use QUI;
use QUI\Cache\Manager as CacheManager;
use QUI\Exception;
use QUI\Utils\Doctrine;

use function is_array;
use function json_decode;
use function json_encode;
use function time;

/**
 * QUIQQER Brick History
 *
 * @author www.pcsg.de (Jan Wennrich)
 */
class Brick
{
    /**
     * The name of the project's table used to store the bricks' history.
     */
    public const PROJECT_TABLE_NAME = 'history_bricks';

    /**
     * Stores a brick in the history.
     * Should be called by the brick save event.
     *
     * @param int $brickId
     *
     * @throws Exception
     */
    public static function onSave(int $brickId): void
    {
        $BrickManager = QUI\Bricks\Manager::init();

        if ($BrickManager === null) {
            throw new Exception('Brick manager not available');
        }

        $Brick = $BrickManager->getBrickById($brickId);

        $cacheKeyLastSave = "history_bricks_last_save_$brickId";

        try {
            $lastSave = CacheManager::get($cacheKeyLastSave);
        } catch (QUI\Cache\Exception) {
            $lastSave = 0;
        }

        $timeSinceLastSave = time() - $lastSave;

        // Don't save if last safe is younger than 10 seconds
        $saveThreshold = 10;
        if ($timeSinceLastSave <= $saveThreshold) {
            return;
        }

        // Store time of this save
        CacheManager::set($cacheKeyLastSave, time(), $saveThreshold);

        static::createHistoryEntry($Brick);
    }

    /**
     * Adds a history entry for the given brick.
     *
     * @param QUI\Bricks\Brick $Brick
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function createHistoryEntry(QUI\Bricks\Brick $Brick): bool
    {
        $brickId = $Brick->getAttribute('id');

        $Project = static::getProjectForBrick($Brick);

        $table = QUI::getDBProjectTableName(static::PROJECT_TABLE_NAME, $Project);

        try {
            QUI::getDataBaseConnection()->insert(Doctrine::quoteIdentifier($table), [
                'id' => $brickId,
                'created' => (new DateTime())->format('Y-m-d H:i:s'),
                'data' => json_encode($Brick->getAttributes()),
                'uid' => QUI::getUserBySession()->getUUID()
            ]);
        } catch (DbalException) {
            // History entry for this brick and date already exists
            return false;
        }

        $historyEntriesLimit = (int)$Project->getConfig('history.limits.limitPerBrick');

        if (empty($historyEntriesLimit)) {
            // No limit set, everything is fine
            return true;
        }

        try {
            $QueryBuilder = QUI::getQueryBuilder();
            $historyEntries = (int)$QueryBuilder
                ->select('COUNT(id)')
                ->from(Doctrine::quoteIdentifier($table))
                ->where($QueryBuilder->expr()->eq('id', ':brickId'))
                ->setParameter('brickId', $brickId)
                ->executeQuery()
                ->fetchOne();
        } catch (DbalException $Exception) {
            QUI\System\Log::writeException($Exception);

            // History entry was successfully created, therefore true is returned
            return true;
        }

        if ($historyEntries <= $historyEntriesLimit) {
            // Limit not reached yet, everything is fine
            return true;
        }

        // How many entries to delete
        $entriesToDeleteCount = $historyEntries - $historyEntriesLimit;

        try {
            $QueryBuilder = QUI::getQueryBuilder();
            $outdatedEntries = $QueryBuilder
                ->select('id', 'created')
                ->from(Doctrine::quoteIdentifier($table))
                ->where($QueryBuilder->expr()->eq('id', ':brickId'))
                ->setParameter('brickId', $brickId)
                ->orderBy('created', 'ASC')
                ->setMaxResults($entriesToDeleteCount)
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (DbalException $Exception) {
            QUI\System\Log::writeException($Exception);

            // History entry was successfully created, therefore true is returned
            return true;
        }

        foreach ($outdatedEntries as $outdatedEntry) {
            QUI::getDataBaseConnection()->delete(Doctrine::quoteIdentifier($table), [
                'id' => $outdatedEntry['id'],
                'created' => $outdatedEntry['created']
            ]);
        }

        return true;
    }

    /**
     * Returns the project the given brick belongs to.
     *
     * @param QUI\Bricks\Brick $Brick
     *
     * @return QUI\Projects\Project
     *
     * @throws Exception
     */
    public static function getProjectForBrick(QUI\Bricks\Brick $Brick): QUI\Projects\Project
    {
        $project = (string)$Brick->getAttribute('project');
        $language = (string)$Brick->getAttribute('lang');

        return QUI\Projects\Manager::getProject($project, $language);
    }

    /**
     * Return the history for a given brick and date.
     *
     * Throws an exception if no entry exists.
     *
     * @param QUI\Bricks\Brick $Brick
     * @param DateTime $Date
     *
     * @return array<string, mixed>
     *
     * @throws QUI\History\Exception\HistoryEntryNotFoundException
     * @throws Exception
     */
    public static function getHistoryEntryData(QUI\Bricks\Brick $Brick, DateTime $Date): array
    {
        $table = QUI::getDBProjectTableName(
            static::PROJECT_TABLE_NAME,
            static::getProjectForBrick($Brick)
        );

        $QueryBuilder = QUI::getQueryBuilder();
        $dataJson = $QueryBuilder
            ->select('data')
            ->from(Doctrine::quoteIdentifier($table))
            ->where($QueryBuilder->expr()->eq('id', ':brickId'))
            ->andWhere($QueryBuilder->expr()->eq('created', ':created'))
            ->setParameter('brickId', $Brick->getAttribute('id'))
            ->setParameter('created', $Date->format('Y-m-d H:i:s'))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        if ($dataJson === false) {
            throw new QUI\History\Exception\HistoryEntryNotFoundException();
        }

        $data = json_decode((string)$dataJson, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Restores the given brick to the state of the given date.
     *
     * @param QUI\Bricks\Brick $Brick
     * @param DateTime $Date
     *
     * @return void
     *
     * @throws Exception
     * @throws QUI\History\Exception\HistoryEntryNotFoundException
     */
    public static function restore(QUI\Bricks\Brick $Brick, DateTime $Date): void
    {
        $BrickManager = QUI\Bricks\Manager::init();

        if ($BrickManager === null) {
            throw new Exception('Brick manager not available');
        }

        $BrickManager->saveBrick(
            $Brick->getAttribute('id'),
            static::getHistoryEntryData($Brick, $Date)
        );
    }

    /**
     * Generates HTML that shows the difference between two versions/dates of a brick.
     *
     * @param QUI\Bricks\Brick $Brick
     * @param DateTime $Date1
     * @param DateTime $Date2
     *
     * @return string
     *
     * @throws Exception
     * @throws QUI\History\Exception\HistoryEntryNotFoundException
     */
    public static function generateDifference(QUI\Bricks\Brick $Brick, DateTime $Date1, DateTime $Date2): string
    {
        $html1 = static::getHistoryEntryData($Brick, $Date1)['content'];
        $html2 = static::getHistoryEntryData($Brick, $Date2)['content'];

        $Diff = new HtmlDiff($html1, $html2);
        $Diff->build();

        return $Diff->getDifference();
    }

    /**
     * Returns an array of all history entries for the given brick.
     *
     * @param QUI\Bricks\Brick $Brick
     *
     * @return list<array{created: string, data: string, uid: string, username: string}>
     *
     * @throws QUI\Database\Exception
     * @throws Exception
     */
    public static function getHistoryEntries(QUI\Bricks\Brick $Brick): array
    {
        $table = QUI::getDBProjectTableName(
            static::PROJECT_TABLE_NAME,
            static::getProjectForBrick($Brick)
        );

        $QueryBuilder = QUI::getQueryBuilder();
        $historyEntries = $QueryBuilder
            ->select('created', 'data', 'uid')
            ->from(Doctrine::quoteIdentifier($table))
            ->where($QueryBuilder->expr()->eq('id', ':brickId'))
            ->setParameter('brickId', $Brick->getAttribute('id'))
            ->orderBy('created', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];

        foreach ($historyEntries as $historyEntry) {
            try {
                $username = QUI::getUsers()->get($historyEntry['uid'])->getName();
            } catch (Exception) {
                $username = '?';
            }

            $result[] = [
                'created' => $historyEntry['created'],
                'data' => $historyEntry['data'],
                'uid' => (string)$historyEntry['uid'],
                'username' => $username
            ];
        }

        return $result;
    }
}
