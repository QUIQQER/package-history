<?php

/**
 * This file contains QDOM
 */

namespace QUI\projects\site;

/**
 * QUIQQER Site History functionality
 *
 * Extends Sites with a history functionality.
 * So you can restore an older state of the site
 *
 * @package com.pcsg.qui
 * @author www.pcsg.de (Henning Leutz)
 */

class History
{
    /**
     * Saves an history
     * @param Projects_Site|Projects_Site_Edit $Site
     */
    static function onSave($Site)
    {
        /* @var $Site Projects_Site_Edit */
        $Site;
    }
}