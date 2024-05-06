<?php

use QUI\Bricks\Manager;
use QUI\History\Brick as BrickHistory;

QUI::$Ajax->registerFunction(
    'package_quiqqer_history_ajax_bricks_restore',
    function ($brickId, $date) {
        BrickHistory::restore(
            Manager::init()->getBrickById($brickId),
            new DateTime($date)
        );
    },
    ['brickId', 'date'],
    'Permission::checkAdminUser'
);
