<?php

/**
 * Return the history entries of a given Site-ID
 *
 * @param String $project - Project data; JSON Array
 * @param String|Integer $id - Site-ID
 * @return Array
 * @throws \QUI\Exception
 */
function package_quiqqer_history_ajax_list($project, $id)
{
    $History = new \QUI\History\Site();
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = $Project->get( $id );

    return $History->getList( $Site );
}

\QUI::$Ajax->register(
    'package_quiqqer_history_ajax_list',
    array( 'project', 'id' )
);
