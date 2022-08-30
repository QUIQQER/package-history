<?php

use QUI\History\Brick as BrickHistory;

QUI::$Ajax->registerFunction(
    'package_quiqqer_history_ajax_bricks_compare',
    function ($brickId, $date1, $date2) {
        $Brick = \QUI\Bricks\Manager::init()->getBrickById($brickId);

        $Date1 = new DateTime($date1);
        $Date2 = new DateTime($date2);

        return [
            "originalHtml"   => BrickHistory::getHistoryEntryData($Brick, $Date1)['content'],
            "differenceHtml" => BrickHistory::generateDifference($Brick, $Date1, $Date2)
        ];
    },
    ['brickId', 'date1', 'date2'],
    'Permission::checkAdminUser'
);
