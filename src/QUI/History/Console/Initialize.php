<?php

namespace QUI\History\Console;

use QUI;
use QUI\Bricks\Brick;
use QUI\Database\Exception;
use QUI\Projects\Project;
use QUI\System\Console\Tool;

class Initialize extends Tool
{
    public function __construct()
    {
        $this->systemTool = true;

        $this->setName('history:initialize')
            ->setDescription(QUI::getLocale()->get('quiqqer/history', 'console.initialize.description'));
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $projects = QUI::getProjectManager()::getProjectList();

        foreach ($projects as $Project) {
            $this->writeLn(
                QUI::getLocale()->get(
                    'quiqqer/history',
                    'console.initialize.process.project',
                    [
                        'projectName' => $Project->getName(),
                        'projectLanguage' => $Project->getLang()
                    ]
                ),
                'default'
            );

            $this->processSites($Project);
            $this->processBricks($Project);
        }
    }

    /**
     * Creates an initial history entry for the sites of the given project.
     *
     * @param Project $Project
     *
     * @return void
     * @throws Exception
     */
    protected function processSites(Project $Project): void
    {
        $siteIds = $Project->getSitesIds();

        foreach ($siteIds as $siteIdData) {
            $siteId = $siteIdData['id'];

            try {
                $Site = $Project->get($siteId);

                $historyEntries = QUI\History\Site::getList($Site);

                if (!empty($historyEntries)) {
                    continue;
                }

                if (method_exists($Site, 'save')) {
                    $Site->save();
                }
            } catch (QUI\Exception $Exception) {
                $this->writeLn(
                    QUI::getLocale()->get(
                        'quiqqer/history',
                        'console.initialize.process.site.error',
                        [
                            'siteId' => $siteId,
                            'reason' => $Exception->getMessage()
                        ]
                    ),
                    'yellow'
                );
            }
        }
    }

    /**
     * Creates an initial history entry for the bricks of the given project.
     *
     * @param Project $Project
     *
     * @return void
     */
    protected function processBricks(Project $Project): void
    {
        $BricksManager = QUI\Bricks\Manager::init();

        try {
            $bricks = $BricksManager->getBricksFromProject($Project);
        } catch (QUI\Exception $Exception) {
            $this->writeLn(
                QUI::getLocale()->get(
                    'quiqqer/history',
                    'console.initialize.process.brick.error.getBricks',
                    [
                        'reason' => $Exception->getMessage()
                    ]
                ),
                'yellow'
            );

            return;
        }

        /** @var Brick[] $bricks */
        foreach ($bricks as $Brick) {
            $brickId = $Brick->getAttribute('id');

            try {
                if (!empty(QUI\History\Brick::getHistoryEntries($Brick))) {
                    continue;
                }

                $BricksManager->saveBrick($brickId, $Brick->getAttributes());
            } catch (QUI\Exception $Exception) {
                $this->writeLn(
                    QUI::getLocale()->get(
                        'quiqqer/history',
                        'console.initialize.process.brick.error.saveBrick',
                        [
                            'brickId' => $brickId,
                            'reason' => $Exception->getMessage()
                        ]
                    ),
                    'yellow'
                );
            }
        }
    }
}
