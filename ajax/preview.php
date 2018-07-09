<?php

/**
 * Return the HTML of the Site-ID from a given history entry
 *
 * @param String $project - Project data; JSON Array
 * @param String|Integer $id - Site-ID
 * @param Integer|\DateTime $date - Timestamp of the history entry
 *
 * @return String - HTML of the History Entry
 * @throws \QUI\Exception
 */
function package_quiqqer_history_ajax_preview($project, $id, $date)
{
    $History = new QUI\History\Site();
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = $Project->get($id);

    if (!isset($_REQUEST['_url'])) {
        $_REQUEST['_url'] = $Site->getUrlRewritten();
    }

    return $History->getHTMLFromHistoryEntry($Site, $date);
}

QUI::$Ajax->register(
    'package_quiqqer_history_ajax_preview',
    ['project', 'id', 'date']
);
