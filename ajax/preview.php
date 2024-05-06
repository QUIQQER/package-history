<?php

/**
 * Return the HTML of the Site-ID from a given history entry
 *
 * @param string $project - Project data; JSON Array
 * @param int|string $id - Site-ID
 * @param DateTime|int $date - Timestamp of the history entry
 *
 * @return string - HTML of the History Entry
 * @throws \QUI\Exception
 */
function package_quiqqer_history_ajax_preview(string $project, int|string $id, DateTime|int $date): string
{
    $History = new QUI\History\Site();
    $Project = QUI::getProjectManager()->decode($project);
    $Site = $Project->get($id);

    if (!isset($_REQUEST['_url'])) {
        $_REQUEST['_url'] = $Site->getUrlRewritten();
    }

    return $History->getHTMLFromHistoryEntry($Site, $date);
}

QUI::$Ajax->register(
    'package_quiqqer_history_ajax_preview',
    ['project', 'id', 'date']
);
