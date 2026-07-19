<?php

namespace QUI\History;

use QUI;
use QUI\System\Console\Tools\MigrationV2;
use QUI\Utils\Doctrine;

class EventHandling
{
    public static function onQuiqqerMigrationV2(MigrationV2 $Console): void
    {
        $Console->writeLn('- Migrate history (archive tables)');
        $projects = QUI::getProjectManager()->getProjects(true);

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            $table = QUI::getDBProjectTableName('archiv', $Project);
            $entries = QUI::getQueryBuilder()
                ->select('id', 'created', 'uid')
                ->from(Doctrine::quoteIdentifier($table))
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($entries as $entry) {
                try {
                    QUI::getDataBaseConnection()->update(
                        Doctrine::quoteIdentifier($table),
                        ['uid' => QUI::getUsers()->get($entry['uid'])->getUUID()],
                        [
                            'id' => $entry['id'],
                            'created' => $entry['created']
                        ]
                    );
                } catch (QUI\Exception) {
                }
            }
        }

        $Console->writeLn('- Migrate brick history (archive brick tables)');

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            $table = QUI::getDBProjectTableName('history_bricks', $Project);
            $entries = QUI::getQueryBuilder()
                ->select('id', 'created', 'uid')
                ->from(Doctrine::quoteIdentifier($table))
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($entries as $entry) {
                try {
                    QUI::getDataBaseConnection()->update(
                        Doctrine::quoteIdentifier($table),
                        ['uid' => QUI::getUsers()->get($entry['uid'])->getUUID()],
                        [
                            'id' => $entry['id'],
                            'created' => $entry['created']
                        ]
                    );
                } catch (QUI\Exception) {
                }
            }
        }
    }
}
