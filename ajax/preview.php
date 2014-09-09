<?php

/**
 * Return user data template
 */

function package_quiqqer_history_ajax_preview($project, $lang, $id, $date)
{
    $History = new \QUI\History\Site();
    $Project = \QUI::getProject( $project, $lang );
    $Site    = $Project->get( $id );

    return $History->getHTMLFromHistoryEntry( $Site, $date );
}

\QUI::$Ajax->register(
    'package_quiqqer_history_ajax_preview',
    array( 'project', 'lang', 'id', 'date' )
);
