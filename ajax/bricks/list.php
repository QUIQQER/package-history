<?php

use QUI\History\Brick as BrickHistory;

QUI::$Ajax->registerFunction(
    'package_quiqqer_history_ajax_bricks_list',
    function ($brickId) {
        $Brick = \QUI\Bricks\Manager::init()->getBrickById($brickId);

        $table = QUI::getDBProjectTableName(
            BrickHistory::PROJECT_TABLE_NAME,
            BrickHistory::getProjectForBrick($Brick)
        );

        $historyEntries = QUI::getDataBase()->fetch([
            'from' => $table,
            'order' => 'created DESC',
            'where' => [
                'id' => $Brick->getAttribute('id')
            ]
        ]);

        $result = [];

        foreach ($historyEntries as $historyEntry) {
            try {
                $username = QUI::getUsers()->get($historyEntry['uid'])->getName();
            } catch (QUI\Exception $Exception) {
                $username = '?';
            }

            $result[] = [
                'created' => $historyEntry['created'],
                'data' => $historyEntry['data'],
                'uid' => $historyEntry['uid'],
                'username' => $username
            ];
        }

        return $result;
    },
    ['brickId'],
    'Permission::checkAdminUser'
);
