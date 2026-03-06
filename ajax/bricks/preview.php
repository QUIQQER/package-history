<?php

use QUI\Bricks\Manager;
use QUI\Exception;
use QUI\History\Brick as BrickHistory;

QUI::getAjax()->registerFunction(
    'package_quiqqer_history_ajax_bricks_preview',
    function ($brickId, $date) {
        $Brick = Manager::init()?->getBrickById($brickId);

        if ($Brick === null) {
            throw new Exception('Brick manager not available');
        }
        $data = BrickHistory::getHistoryEntryData($Brick, new DateTime($date));

        return $data['content'];
    },
    ['brickId', 'date'],
    'Permission::checkAdminUser'
);
