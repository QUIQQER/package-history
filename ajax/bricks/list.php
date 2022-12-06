<?php

use QUI\Bricks\Manager as BrickManager;
use QUI\History\Brick as BrickHistory;

QUI::$Ajax->registerFunction(
    'package_quiqqer_history_ajax_bricks_list',
    function ($brickId) {
        $Brick = BrickManager::init()->getBrickById($brickId);

        return BrickHistory::getHistoryEntries($Brick);
    },
    ['brickId'],
    'Permission::checkAdminUser'
);
