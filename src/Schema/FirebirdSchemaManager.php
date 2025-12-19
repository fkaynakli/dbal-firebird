<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\FirebirdPlatform;

/**
 * Firebird Schema Manager.
 */
class FirebirdSchemaManager extends AbstractSchemaManager
{
    public function __construct(Connection $connection, FirebirdPlatform $platform)
    {
        parent::__construct($connection, $platform);
    }

    protected function selectTableNames(string $databaseName): Result
    {
        return $this->connection->executeQuery(
            "SELECT RDB\$RELATION_NAME FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$VIEW_BLR IS NULL"
        );
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = "SELECT r.RDB\$RELATION_NAME, f.RDB\$FIELD_NAME, f.RDB\$FIELD_POSITION, f.RDB\$DEFAULT_SOURCE, f.RDB\$NULL_FLAG, t.RDB\$TYPE_NAME, fld.RDB\$FIELD_LENGTH, fld.RDB\$FIELD_PRECISION, fld.RDB\$FIELD_SCALE FROM RDB\$RELATION_FIELDS f JOIN RDB\$RELATIONS r ON f.RDB\$RELATION_NAME = r.RDB\$RELATION_NAME JOIN RDB\$FIELDS fld ON f.RDB\$FIELD_SOURCE = fld.RDB\$FIELD_NAME JOIN RDB\$TYPES t ON fld.RDB\$FIELD_TYPE = t.RDB\$TYPE WHERE r.RDB\$SYSTEM_FLAG = 0 AND t.RDB\$FIELD_NAME = 'RDB\$FIELD_TYPE'";

        if ($tableName !== null) {
            $sql .= " AND r.RDB\$RELATION_NAME = " . $this->connection->quote($tableName);
        }

        return $this->connection->executeQuery($sql);
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = "SELECT i.RDB\$RELATION_NAME, i.RDB\$INDEX_NAME, i.RDB\$UNIQUE_FLAG, s.RDB\$FIELD_NAME, s.RDB\$FIELD_POSITION FROM RDB\$INDICES i JOIN RDB\$INDEX_SEGMENTS s ON i.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME WHERE i.RDB\$FOREIGN_KEY IS NULL";

        if ($tableName !== null) {
            $sql .= " AND i.RDB\$RELATION_NAME = " . $this->connection->quote($tableName);
        }

        return $this->connection->executeQuery($sql);
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = "SELECT rc.RDB\$RELATION_NAME, rc.RDB\$CONSTRAINT_NAME, idx.RDB\$RELATION_NAME AS REFERENCED_TABLE_NAME, s.RDB\$FIELD_NAME, rs.RDB\$FIELD_NAME AS REFERENCED_FIELD_NAME FROM RDB\$RELATION_CONSTRAINTS rc JOIN RDB\$REF_CONSTRAINTS ref ON rc.RDB\$CONSTRAINT_NAME = ref.RDB\$CONSTRAINT_NAME JOIN RDB\$INDICES idx ON ref.RDB\$CONST_NAME_UQ = idx.RDB\$INDEX_NAME JOIN RDB\$INDEX_SEGMENTS s ON rc.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME JOIN RDB\$INDEX_SEGMENTS rs ON idx.RDB\$INDEX_NAME = rs.RDB\$INDEX_NAME WHERE rc.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'";

        if ($tableName !== null) {
            $sql .= " AND rc.RDB\$RELATION_NAME = " . $this->connection->quote($tableName);
        }

        return $this->connection->executeQuery($sql);
    }

    protected function fetchTableOptionsByTable(string $databaseName, ?string $tableName = null): array
    {
        return [];
    }

    protected function _getPortableTableColumnDefinition(array $tableColumn): Column
    {
        $dbType = trim($tableColumn['RDB$TYPE_NAME']);
        $type = $this->platform->getDoctrineTypeMapping($dbType);

        $options = [
            'length' => $tableColumn['RDB$FIELD_LENGTH'] ?? null,
            'notnull' => isset($tableColumn['RDB$NULL_FLAG']),
            'default' => $tableColumn['RDB$DEFAULT_SOURCE'] ?? null,
        ];

        return new Column(trim($tableColumn['RDB$FIELD_NAME']), Type::getType($type), $options);
    }

    protected function _getPortableTableDefinition(array $table): string
    {
        return trim($table['RDB$RELATION_NAME']);
    }

    protected function _getPortableViewDefinition(array $view): View
    {
        return new View(trim($view['RDB$RELATION_NAME']), '');
    }

    protected function _getPortableTableForeignKeyDefinition(array $tableForeignKey): ForeignKeyConstraint
    {
        return new ForeignKeyConstraint(
            [trim($tableForeignKey['RDB$FIELD_NAME'])],
            trim($tableForeignKey['REFERENCED_TABLE_NAME']),
            [trim($tableForeignKey['REFERENCED_FIELD_NAME'])],
            trim($tableForeignKey['RDB$CONSTRAINT_NAME'])
        );
    }
}
