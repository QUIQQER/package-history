<?php

/**
 * Return user data template
 */

function package_quiqqer_history_ajax_list($project, $lang, $id)
{
    $History = new \QUI\History\Site();
    $Project = \QUI::getProject( $project, $lang );
    $Site    = $Project->get( $id );

    return $History->getList( $Site );
}

\QUI::$Ajax->register(
    'package_quiqqer_history_ajax_list',
    array( 'project', 'lang', 'id' )
);
