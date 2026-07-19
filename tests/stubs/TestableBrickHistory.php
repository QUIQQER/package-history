<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use QUI\Bricks\Brick;
use QUI\History\Brick as BrickHistory;
use QUI\Projects\Project;

class TestableBrickHistory extends BrickHistory
{
    public static Project $Project;

    public static function getProjectForBrick(Brick $Brick): Project
    {
        return self::$Project;
    }
}
