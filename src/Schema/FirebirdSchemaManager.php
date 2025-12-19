<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Connection;
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
}
