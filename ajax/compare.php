<?php

/**
 * Return user data template
 */

function package_quiqqer_history_ajax_compare($project, $lang, $id, $date1, $date2)
{
    $History = new \QUI\Projects\Site\History();
    $Project = \QUI::getProject( $project, $lang );
    $Site    = $Project->get( $id );

    return $History->getDiffFromSite( $Site, $date1, $date2 );
}

\QUI::$Ajax->register(
    'package_quiqqer_history_ajax_compare',
    array( 'project', 'lang', 'id', 'date1', 'date2' )
);
