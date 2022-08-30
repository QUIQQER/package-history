<?php

namespace QUI\History;

use DateTime;

use QUI;
use QUI\Cache\Manager as CacheManager;
use QUI\Exception;

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
     * The name of the project's table used to store the bricks history.
     */
    protected const PROJECT_TABLE_NAME = 'history_bricks';

    /**
     * Stores a brick in the history.
     * Should be called by the brick save event.
     *
     * @param int $brickId
     *
     * @throws QUI\Exception
     */
    public static function onSave(int $brickId)
    {
        $Brick = QUI\Bricks\Manager::init()->getBrickById($brickId);

        $cacheKeyLastSave = "history_bricks_last_save_${brickId}";

        try {
            $lastSave = CacheManager::get($cacheKeyLastSave);
        } catch (QUI\Cache\Exception $Exception) {
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

        $project  = $Brick->getAttribute('project');
        $language = $Brick->getAttribute('lang');

        $Project = QUI\Projects\Manager::getProject($project, $language);

        $table = QUI::getDBProjectTableName(static::PROJECT_TABLE_NAME, $Project);

        try {
            QUI::getDataBase()->insert($table, [
                'id' => $brickId,
                'created' => (new DateTime())->format('Y-m-d H:i:s'),
                'data' => json_encode($Brick->getAttributes()),
                'uid' => QUI::getUserBySession()->getId()
            ]);
        } catch (QUI\Database\Exception $Exception) {
            // History entry for this brick and date already exists
            return false;
        }

        $historyEntriesLimit = (int)$Project->getConfig('history.limits.limitPerBrick');

        if (empty($historyEntriesLimit)) {
            // No limit set, everything is fine
            return true;
        }

        try {
            $result = QUI::getDataBase()->fetch([
                'from' => $table,
                'count' => [
                    'select' => 'id',
                    'as' => 'count'
                ],
                'where' => [
                    'id' => $brickId
                ]
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            // History entry was successfully created, therefore true is returned
            return true;
        }

        $historyEntries = (int)$result[0]['count'];

        if ($historyEntries <= $historyEntriesLimit) {
            // Limit not reached yet, everything is fine
            return true;
        }

        // How many entries to delete
        $entriesToDeleteCount = $historyEntries - $historyEntriesLimit;

        try {
            $outdatedEntries = QUI::getDataBase()->fetch([
                'from' => $table,
                'where' => [
                    'id' => $brickId
                ],
                'order' => 'created ASC',
                'limit' => $entriesToDeleteCount
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            // History entry was successfully created, therefore true is returned
            return true;
        }

        // Some MySQL versions don't support deleting with limit & offset, therefore this foreach loop is used
        foreach ($outdatedEntries as $outdatedEntry) {
            QUI::getDataBase()->delete($table, $outdatedEntry);
        }

        return true;
    }

    /**
     * Return the history for a given brick and date.
     *
     * Throws an exception if no entry exists.
     *
     * @param QUI\Bricks\Brick $Brick
     * @param \DateTime $date
     *
     * @return array
     *
     * @throws QUI\History\Exception\HistoryEntryNotFoundException
     */
    public static function getHistoryEntry(QUI\Bricks\Brick $Brick, \DateTime $date): array
    {
        // TODO: implement method body
        throw new QUI\History\Exception\HistoryEntryNotFoundException();
    }
}
