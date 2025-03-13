<?php

use QUI\Exception;

/**
 * Compare two history entries with each other
 * And return the comparison
 *
 * @param String $project - Project data; JSON Array
 * @param Integer|String $id - Site-ID
 * @param DateTime|int|string $date1
 * @param DateTime|int|string $date2
 *
 * @return array
 * @throws Exception
 */

function package_quiqqer_history_ajax_compare(
    string $project,
    int | string $id,
    DateTime | int | string $date1,
    DateTime | int | string $date2
): array {
    $History = new QUI\History\Site();
    $Project = QUI::getProjectManager()->decode($project);
    $Site = $Project->get($id);

    if (is_string($date1)) {
        $date1 = (int)$date1;
    }

    if (is_string($date2)) {
        $date2 = (int)$date2;
    }

    $originalHTML = QUI\History\Site::getHTMLFromHistoryEntry($Site, $date1);
    $difference = $History->getDiffFromSite($Site, $date1, $date2);

    return [
        "originalHtml" => $originalHTML,
        "differenceHtml" => $difference
    ];
}

QUI::$Ajax->register(
    'package_quiqqer_history_ajax_compare',
    ['project', 'id', 'date1', 'date2']
);
