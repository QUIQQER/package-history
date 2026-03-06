<?php

use QUI\Bricks\Manager as BrickManager;
use QUI\Exception;
use QUI\History\Brick as BrickHistory;

QUI::getAjax()->registerFunction(
    'package_quiqqer_history_ajax_bricks_compare',
    function ($brickId, $date1, $date2) {
        $Brick = BrickManager::init()?->getBrickById($brickId);

        if ($Brick === null) {
            throw new Exception('Brick manager not available');
        }

        $dates = [new DateTime($date1), new DateTime($date2)];

        $DateOldest = min($dates);
        $DateNewest = max($dates);

        return [
            "originalHtml" => BrickHistory::getHistoryEntryData($Brick, $DateOldest)['content'],
            "differenceHtml" => BrickHistory::generateDifference($Brick, $DateOldest, $DateNewest)
        ];
    },
    ['brickId', 'date1', 'date2'],
    'Permission::checkAdminUser'
);
