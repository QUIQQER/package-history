<?php

use QUI\History\Site;

/**
 * Restore a history entry of a given site
 *
 * @param string $project - Project data; JSON Array
 * @param int|string $id - Site-ID
 * @param DateTime|int $date - Timestamp of the history entry
 *
 * @throws \QUI\Exception
 */
function package_quiqqer_history_ajax_restore(string $project, int|string $id, DateTime|int $date): void
{
    $History = new Site();
    $Project = QUI::getProjectManager()->decode($project);
    $Site = $Project->get($id);

    $History->restoreSite($Site, $date);
}

QUI::$Ajax->register(
    'package_quiqqer_history_ajax_restore',
    ['project', 'id', 'date']
);
