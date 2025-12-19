<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\Firebird\ExceptionConverter;
use Doctrine\DBAL\Platforms\FirebirdPlatform;
use Doctrine\DBAL\ServerVersionProvider;

/**
 * Abstract base implementation of the {@see Driver} interface for Firebird based drivers.
 */
abstract class AbstractFirebirdDriver implements Driver
{
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): FirebirdPlatform
    {
        return new FirebirdPlatform();
    }

    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }
}
