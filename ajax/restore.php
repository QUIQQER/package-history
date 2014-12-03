<?php

/**
 * Restore a history entry of a given site
 *
 * @param String $project - Project data; JSON Array
 * @param String|Integer $id - Site-ID
 * @param Integer|\DateTime $date - Timestamp of the history entry
 * @throws \QUI\Exception
 */
function package_quiqqer_history_ajax_restore($project, $id, $date)
{
    $History = new \QUI\History\Site();
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = $Project->get( $id );

    $History->restoreSite( $Site, $date );
}

\QUI::$Ajax->register(
    'package_quiqqer_history_ajax_restore',
    array( 'project', 'id', 'date' )
);
