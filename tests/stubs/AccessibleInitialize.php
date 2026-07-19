<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use QUI\History\Console\Initialize;
use QUI\Projects\Project;

class AccessibleInitialize extends Initialize
{
    public function processSitesPublic(Project $Project): void
    {
        $this->processSites($Project);
    }

    public function processBricksPublic(Project $Project): void
    {
        $this->processBricks($Project);
    }
}
