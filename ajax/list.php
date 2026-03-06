<?php

use QUI\History\Site;

/**
 * Return the history entries of a given Site-ID
 *
 * @param string $project - Project data; JSON Array
 * @param int|string $id - Site-ID
 *
 * @return list<array{created: string, data: string, uid: string, username: string}>
 * @throws \QUI\Exception
 */
function package_quiqqer_history_ajax_list(string $project, int|string $id): array
{
    $History = new Site();
    $Project = QUI::getProjectManager()->decode($project);
    $Site = $Project->get((int)$id);

    return array_values($History->getList($Site));
}

QUI::getAjax()->register(
    'package_quiqqer_history_ajax_list',
    ['project', 'id']
);
