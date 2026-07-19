<?php

declare(strict_types=1);

namespace QUITests\History\Integration;

use PDOException;
use PDOStatement;
use QUI;
use QUI\Database\DB;

/**
 * The legacy database wrapper generates MySQL-only INSERT ... SET syntax.
 * Keep production unchanged in the pre-migration test phase while writing the
 * integration fixtures and assertions through DBAL against SQLite.
 */
class SqliteCompatibleLegacyDatabase extends DB
{
    public function insert(string $table, array $data): PDOStatement
    {
        $Platform = $this->Doctrine->getDatabasePlatform();
        $columns = array_map($Platform->quoteIdentifier(...), array_keys($data));
        $placeholders = array_fill(0, count($data), '?');
        $Statement = $this->PDO->prepare(
            'INSERT INTO ' . $Platform->quoteIdentifier($table)
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')'
        );

        try {
            $Statement->execute(array_values($data));
        } catch (PDOException $Exception) {
            throw new QUI\Database\Exception($Exception->getMessage(), (int)$Exception->getCode());
        }

        return $Statement;
    }

    public function update(string $table, array $data, array|string $where): PDOStatement
    {
        if (!is_array($where)) {
            throw new QUI\Database\Exception('The SQLite test adapter requires array criteria');
        }

        $Platform = $this->Doctrine->getDatabasePlatform();
        $set = [];
        $criteria = [];
        $values = [];

        foreach ($data as $column => $value) {
            $set[] = $Platform->quoteIdentifier((string)$column) . ' = ?';
            $values[] = $value;
        }

        foreach ($where as $column => $value) {
            $criteria[] = $Platform->quoteIdentifier((string)$column) . ' = ?';
            $values[] = $value;
        }

        $Statement = $this->PDO->prepare(
            'UPDATE ' . $Platform->quoteIdentifier($table)
            . ' SET ' . implode(', ', $set)
            . ' WHERE ' . implode(' AND ', $criteria)
        );
        $Statement->execute($values);

        return $Statement;
    }
}
