<?php

namespace QUI\History;

use QUI;

/**
 * QUIQQER Brick History
 *
 * @author www.pcsg.de (Jan Wennrich)
 */
class Brick
{
    /**
     * Stores a brick in the history.
     * Should be called by the brick save event.
     *
     * @param int $brickId
     *
     * @throws QUI\Exception
     */
    public static function onSave(int $brickId)
    {
        // TODO: implement method body
    }
}
