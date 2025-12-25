<?php

declare(strict_types=1);

namespace Foodsoft\FirebirdDriver\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

final class FirebirdSchemaManager extends AbstractSchemaManager
{
    public function __construct(Connection $conn, ?\Doctrine\DBAL\Platforms\AbstractPlatform $platform = null)
    {
        parent::__construct($conn, $platform);
    }

    /**
     * @param mixed[] $tableColumn
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $typeName = $this->platform->getDoctrineTypeMapping($tableColumn['type'] ?? 'string');
        return new Column($tableColumn['name'] ?? 'column', Type::getType($typeName));
    }
}
