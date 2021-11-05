<?php

/**
 * This file contains \QUI\History\Site
 */

namespace QUI\History;

use QUI;
use QUI\Cache\Manager as CacheManager;
use \PCSG\PhpHtmlDiff\HtmlDiff;

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
     * internal cache
     * @var array
     */
    public static $cache = [];

    /**
     * Saves an history entry
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     *
     * @throws QUI\Exception
     */
    public static function onSave($Site)
    {
        $Project = $Site->getProject();
        $table   = QUI::getDBProjectTableName('archiv', $Project);

        $cacheId = $Project->getName().'_'.
                   $Project->getLang().'_'.
                   $Site->getId();

        try {
            $cacheTime = CacheManager::get($cacheId);
        } catch (QUI\Cache\Exception $Exception) {
            $cacheTime = 0;
        }

        // wait 10 seconds
        // we need not every 10 seconds a history entry
        // @todo maybe settings for minutes or hours
        $cacheTTL = 10;
        $diff     = time() - $cacheTime;

        if ($diff <= $cacheTTL) {
            return;
        }

        CacheManager::set($cacheId, time(), $cacheTTL);

        try {
            $created = date('Y-m-d H:i:s');

            $countResult = QUI::getDataBase()->fetch([
                'count' => 1,
                'from'  => $table,
                'where' => [
                    'id'      => $Site->getId(),
                    'created' => $created
                ]
            ]);

            if (empty(\current(\current($countResult)))) {
                QUI::getDataBase()->insert($table, [
                    'id'      => $Site->getId(),
                    'created' => $created,
                    'data'    => json_encode($Site->getAttributes()),
                    'uid'     => QUI::getUserBySession()->getId()
                ]);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addAlert($Exception->getMessage());

            return;
        }

        // check limit
        $limit = $Project->getConfig('history.limits.limitPerSite');

        if ($limit === false || !$limit) {
            return;
        }

        $result = QUI::getDataBase()->fetch([
            'from'  => $table,
            'count' => [
                'select' => 'id',
                'as'     => 'count'
            ],
            'where' => [
                'id' => $Site->getId()
            ]
        ]);

        $count = (int)$result[0]['count'];

        if ($count < $limit) {
            return;
        }

        // delete the oldest
        $overflow = $count - $limit;

        // could not delete directly
        // some mysql version dont support that, so we must delete the entries in an extra step
        $result = QUI::getDataBase()->fetch([
            'from'  => $table,
            'where' => [
                'id' => $Site->getId()
            ],
            'order' => 'created ASC',
            'limit' => '0,'.$overflow
        ]);

        foreach ($result as $entry) {
            QUI::getDataBase()->delete($table, $entry);
        }
    }

    /**
     * Return the history from a Site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public static function getList($Site)
    {
        $Project = $Site->getProject();
        $table   = QUI::getDBProjectTableName('archiv', $Project);
        $result  = [];


        $list = QUI::getDataBase()->fetch([
            'from'  => $table,
            'order' => 'created DESC',
            'where' => [
                'id' => $Site->getId()
            ]
        ]);

        foreach ($list as $entry) {
            $username = '';

            try {
                $User     = QUI::getUsers()->get($entry['uid']);
                $username = $User->getName();
            } catch (QUI\Exception $Exception) {
            }

            $result[] = [
                'created'  => $entry['created'],
                'data'     => $entry['data'],
                'uid'      => $entry['uid'],
                'username' => $username
            ];
        }

        return $result;
    }

    /**
     * Return the history entry from a site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param integer|\DateTime $date
     *
     * @return array
     * @throws QUI\Exception
     */
    public static function getHistoryEntry($Site, $date)
    {
        $Date = new \DateTime($date);

        $Project = $Site->getProject();
        $table   = QUI::getDBProjectTableName('archiv', $Project);

        $result = QUI::getDataBase()->fetch([
            'from'  => $table,
            'where' => [
                'created' => $Date->format('Y-m-d H:i:s')
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                'History entry not exist'
            );
        }

        $data = json_decode($result[0]['data'], true);

        return is_array($data) ? $data : [];
    }

    /**
     * Return the html from a history entry from a site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param integer|\DateTime $date - Timestamp | Date
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public static function getHTMLFromHistoryEntry($Site, $date)
    {
        $data = self::getHistoryEntry($Site, $date);

        if (isset($data['type'])) {
            $Site->setAttribute('type', $data['type']);
        }

        $Site->load();

        // site data
        foreach ($data as $key => $value) {
            $Site->setAttribute($key, $value);
        }

//        $Rewrite->setSite($Site);
//        $Rewrite->setPath($Site->getParents());
//        $Rewrite->addSiteToPath($Site);

        $content = QUI::getTemplateManager()->fetchSite($Site);

        $packageDir = QUI::getPackage('quiqqer/history')->getDir();
        QUI\Control\Manager::addCSSFile("{$packageDir}/bin/SiteCompare.css");

        $content = QUI\Control\Manager::setCSSToHead($content);

        $Output  = new QUI\Output();
        $content = $Output->parse($content);

        return $content;
    }

    /**
     * Return the diff between to history entries from a site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param integer|\DateTime $date1 - Timestamp | Date
     * @param integer|\DateTime $date2 - Timestamp | Date
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public static function getDiffFromSite($Site, $date1, $date2)
    {
        $entry1 = self::getHTMLFromHistoryEntry($Site, $date1);
        $entry2 = self::getHTMLFromHistoryEntry($Site, $date2);

        $Diff = new HtmlDiff($entry1, $entry2);
        $Diff->build();

        return $Diff->getDifference();
    }

    /**
     * restore a history entry from a site
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param integer|\DateTime $date - Timestamp | Date
     *
     * @throws QUI\Exception
     */
    public static function restoreSite($Site, $date)
    {
        $Project = $Site->getProject();
        $data    = self::getHistoryEntry($Site, $date);

        $SiteEdit = new QUI\Projects\Site\Edit($Project, $Site->getId());

        if (isset($data['type'])) {
            $SiteEdit->setAttribute('type', $data['type']);
        }

        // site data
        foreach ($data as $key => $value) {
            $SiteEdit->setAttribute($key, $value);
        }

        $SiteEdit->save();
    }
}
