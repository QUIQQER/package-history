<?php

/**
 * Compare two history entries with each other
 * And return the comparison
 *
 * @param String $project - Project data; JSON Array
 * @param String|Integer $id - Site-ID
 * @param                $date1
 * @param                $date2
 *
 * @return String
 * @throws \QUI\Exception
 */

function package_quiqqer_history_ajax_compare($project, $id, $date1, $date2)
{
    $History = new QUI\History\Site();
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = $Project->get($id);

    $originalHTML = QUI\History\Site::getHTMLFromHistoryEntry($Site, $date1);
    $difference   = $History->getDiffFromSite($Site, $date1, $date2);

    return [
        "originalHtml"   => $originalHTML,
        "differenceHtml" => $difference
    ];
}

QUI::$Ajax->register(
    'package_quiqqer_history_ajax_compare',
    ['project', 'id', 'date1', 'date2']
);
