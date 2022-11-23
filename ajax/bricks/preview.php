<?php

use QUI\History\Brick as BrickHistory;

QUI::$Ajax->registerFunction(
    'package_quiqqer_history_ajax_bricks_preview',
    function ($brickId, $date) {
        $Brick = \QUI\Bricks\Manager::init()->getBrickById($brickId);

        $data = BrickHistory::getHistoryEntryData($Brick, new DateTime($date));

        return $data['content'];
    },
    ['brickId', 'date'],
    'Permission::checkAdminUser'
);
