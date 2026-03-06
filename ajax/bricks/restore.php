<?php

use QUI\Bricks\Manager;
use QUI\Exception;
use QUI\History\Brick as BrickHistory;

QUI::getAjax()->registerFunction(
    'package_quiqqer_history_ajax_bricks_restore',
    function ($brickId, $date) {
        $Brick = Manager::init()?->getBrickById($brickId);

        if ($Brick === null) {
            throw new Exception('Brick manager not available');
        }

        BrickHistory::restore(
            $Brick,
            new DateTime($date)
        );
    },
    ['brickId', 'date'],
    'Permission::checkAdminUser'
);
