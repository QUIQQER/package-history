<?php

/**
 * Return user data template
 */

function package_quiqqer_history_ajax_restore($project, $lang, $id, $date)
{
    $History = new \QUI\History\Site();
    $Project = \QUI::getProject( $project, $lang );
    $Site    = $Project->get( $id );

    $History->restoreSite( $Site, $date );
}

\QUI::$Ajax->register(
    'package_quiqqer_history_ajax_restore',
    array( 'project', 'lang', 'id', 'date' )
);
