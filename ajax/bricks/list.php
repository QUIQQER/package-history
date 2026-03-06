<?php

use QUI\Bricks\Manager as BrickManager;
use QUI\Exception;
use QUI\History\Brick as BrickHistory;

QUI::getAjax()->registerFunction(
    'package_quiqqer_history_ajax_bricks_list',
    function ($brickId) {
        $Brick = BrickManager::init()?->getBrickById($brickId);

        if ($Brick === null) {
            throw new Exception('Brick manager not available');
        }
        return BrickHistory::getHistoryEntries($Brick);
    },
    ['brickId'],
    'Permission::checkAdminUser'
);
