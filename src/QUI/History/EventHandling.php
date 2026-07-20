<?php

namespace QUI\History;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use QUI;
use QUI\Package\Package;
use QUI\System\Console\Tools\MigrationV2;
use QUI\Utils\Doctrine;

class EventHandling
{
    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public static function onPackageSetup(Package $Package): void
    {
        if ($Package->getName() !== 'quiqqer/history') {
            return;
        }

        self::migrateUidColumns();
    }

    public static function onQuiqqerMigrationV2(MigrationV2 $Console): void
    {
        self::migrateUidColumns();

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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private static function migrateUidColumns(): void
    {
        $projects = QUI::getProjectManager()->getProjects(true);

        foreach ($projects as $Project) {
            foreach (['archiv', 'history_bricks'] as $suffix) {
                self::migrateUidColumn(QUI::getDBProjectTableName($suffix, $Project));
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private static function migrateUidColumn(string $tableName): void
    {
        $SchemaManager = QUI::getSchemaManager();

        if (!$SchemaManager->tablesExist([$tableName])) {
            return;
        }

        $Table = $SchemaManager->introspectTable($tableName);

        if (!$Table->hasColumn('uid')) {
            return;
        }

        $CurrentColumn = $Table->getColumn('uid');

        if ($CurrentColumn->getType() instanceof StringType) {
            return;
        }

        $TargetColumn = new Column(
            'uid',
            Type::getType(Types::STRING),
            [
                'length' => 50,
                'notnull' => $CurrentColumn->getNotnull(),
                'default' => $CurrentColumn->getDefault()
            ]
        );

        $SchemaManager->alterTable(new TableDiff(
            $Table,
            changedColumns: [
                'uid' => new ColumnDiff($CurrentColumn, $TargetColumn)
            ]
        ));
    }
}
