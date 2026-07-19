<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use QUI\History\Console\Initialize;
use QUI\Projects\Project;

class RecordingInitialize extends Initialize
{
    /** @var list<Project> */
    public array $siteProjects = [];

    /** @var list<Project> */
    public array $brickProjects = [];

    protected function processSites(Project $Project): void
    {
        $this->siteProjects[] = $Project;
    }

    protected function processBricks(Project $Project): void
    {
        $this->brickProjects[] = $Project;
    }
}
