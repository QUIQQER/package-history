<?php

/**
 * This file contains \QUI\History\Site
 */

namespace QUI\History;

use QUI;

/**
 * QUIQQER Site History functionality
 *
 * Provided history functionality.
 * So you can restore an older state of a site
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Site
{
    /**
     * Saves an history entry
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     */
    static function onSave($Site)
    {
        $Project = $Site->getProject();
        $table   = QUI::getDBProjectTableName( 'archiv', $Project );

        QUI::getDataBase()->insert($table, array(
            'id'      => $Site->getId(),
            'created' => date( 'Y-m-d H:i:s' ),
            'data'    => json_encode( $Site->getAttributes() ),
            'uid'     => QUI::getUserBySession()->getId()
        ));

        // check limit
        $limit = $Project->getConfig( 'history.limits.limitPerSite' );

        if ( $limit === false || !$limit ) {
            return;
        }

        $result = QUI::getDataBase()->fetch(array(
            'from' => $table,
            'count' => array(
                'select' => 'id',
                'as'     => 'count'
            ),
            'where' => array(
                'id' => $Site->getId()
            )
        ));

        $count = (int)$result[ 0 ][ 'count' ];

        if ( $count < $limit ) {
            return;
        }

        // delete the oldest
        $overflow = $count - $limit;

        // could not delete directly
        // some mysql version dont support that, so we must delete the entries in an extra step
        $result = QUI::getDataBase()->fetch(array(
            'from'   => $table,
            'where'  => array(
                'id' => $Site->getId()
            ),
            'order' => 'created ASC',
            'limit' => '0,'. $overflow
        ));

        foreach ( $result as $entry ) {
            QUI::getDataBase()->delete( $table, $entry );
        }
    }

    /**
     * Return the history from a Site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @return Array
     */
    static function getList($Site)
    {
        $Project = $Site->getProject();
        $table   = QUI::getDBProjectTableName( 'archiv', $Project );
        $result  = array();


        $list = QUI::getDataBase()->fetch(array(
            'from'  => $table,
            'order' => 'created DESC',
            'where' => array(
                'id' => $Site->getId()
            )
        ));

        foreach ( $list as $entry )
        {
            $username = '';

            try
            {
                $User     = QUI::getUsers()->get( $entry['uid'] );
                $username = $User->getName();

            } catch ( QUI\Exception $Exception )
            {

            }

            $result[] = array(
                'created'  => $entry['created'],
                'data'     => $entry['data'],
                'uid'      => $entry['uid'],
                'username' => $username
            );
        }

        return $result;
    }

    /**
     * Return the history entry from a site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param Integer|\DateTime $date
     * @return Array
     * @throws QUI\Exception
     */
    static function getHistoryEntry($Site, $date)
    {
        $Date = new \DateTime( $date );

        $Project = $Site->getProject();
        $table   = QUI::getDBProjectTableName( 'archiv', $Project );

        $result = QUI::getDataBase()->fetch(array(
            'from'  => $table,
            'where' => array(
                'created' => $Date->format('Y-m-d H:i:s')
            ),
            'limit' => 1
        ));

        if ( !isset( $result[0] ) )
        {
            throw new QUI\Exception(
                'History entry not exist'
            );
        }

        $data = json_decode( $result[0]['data'], true );

        return is_array( $data ) ? $data : array();
    }

    /**
     * Return the html from a history entry from a site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param Integer|\DateTime $date - Timestamp | Date
     * @return String
     */
    static function getHTMLFromHistoryEntry($Site, $date)
    {
        $data = self::getHistoryEntry( $Site, $date );

        if ( isset( $data['type'] ) ) {
            $Site->setAttribute( 'type', $data['type'] );
        }

        $Site->load();

        // site data
        foreach ( $data as $key => $value ) {
            $Site->setAttribute( $key, $value );
        }

        $content = QUI::getTemplateManager()->fetchTemplate( $Site );
        $content = QUI::getRewrite()->outputFilter( $content );

        return $content;
    }

    /**
     * Return the diff between to history entries from a site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param Integer|\DateTime $date1 - Timestamp | Date
     * @param Integer|\DateTime $date2 - Timestamp | Date
     *
     * @return String
     */
    static function getDiffFromSite($Site, $date1, $date2)
    {
        $entry1 = self::getHTMLFromHistoryEntry( $Site, $date1 );
        $entry2 = self::getHTMLFromHistoryEntry( $Site, $date2 );

        if ( !class_exists( 'HtmlDiff' ) ) {
            require_once OPT_DIR .'rashid2538/php-htmldiff/HtmlDiff.php';
        }

        $Diff = new \HtmlDiff( $entry1, $entry2 );
        $Diff->build();

        return $Diff->getDifference();
    }

    /**
     * restore a history entry from a site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param Integer|\DateTime $date - Timestamp | Date
     */
    static function restoreSite($Site, $date)
    {
        $Project = $Site->getProject();
        $data    = self::getHistoryEntry( $Site, $date );

        $SiteEdit = new QUI\Projects\Site\Edit( $Project,  $Site->getId() );

        if ( isset( $data['type'] ) ) {
            $SiteEdit->setAttribute( 'type', $data['type'] );
        }

        // site data
        foreach ( $data as $key => $value ) {
            $SiteEdit->setAttribute( $key, $value );
        }

        $SiteEdit->save();
    }
}
