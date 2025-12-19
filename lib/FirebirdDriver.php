<?php

declare(strict_types=1);

namespace Foodsoft\FirebirdDriver;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\ServerVersionProvider;
use Foodsoft\FirebirdDriver\Platform\FirebirdPlatform;
use Foodsoft\FirebirdDriver\Exception\ExceptionConverter as FirebirdExceptionConverter;

/**
 * Firebird PDO driver for Doctrine DBAL.
 */
final class FirebirdDriver implements Driver
{
    use \Doctrine\DBAL\Driver\PDO\PDOConnect;

    public function connect(
        #[\SensitiveParameter]
        array $params
    ): \Doctrine\DBAL\Driver\Connection {
        $driverOptions = [];

        if (isset($params['driverOptions'])) {
            $driverOptions = $params['driverOptions'];
        }

        if (! empty($params['persistent'])) {
            $driverOptions[\PDO::ATTR_PERSISTENT] = true;
        }

        foreach (['user', 'password'] as $key) {
            if (isset($params[$key]) && ! is_string($params[$key])) {
                throw \Doctrine\DBAL\Driver\PDO\Exception\InvalidConfiguration::notAStringOrNull($key, $params[$key]);
            }
        }

        $safeParams = $params;
        unset($safeParams['password']);

        try {
            $pdo = $this->doConnect(
                $this->constructDsn($safeParams),
                $params['user'] ?? '',
                $params['password'] ?? '',
                $driverOptions,
            );
        } catch (\PDOException $exception) {
            throw \Doctrine\DBAL\Driver\PDO\Exception::new($exception);
        }

        return new \Doctrine\DBAL\Driver\PDO\Connection($pdo);
    }

    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
    {
        return new FirebirdPlatform();
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return new FirebirdExceptionConverter();
    }

    /**
     * Constructs the Firebird PDO DSN.
     *
     * @param mixed[] $params
     */
    private function constructDsn(array $params): string
    {
        $dsn = 'firebird:';

        // dbname can be either a path or host/port:path format
        if (isset($params['dbname'])) {
            if (isset($params['host'])) {
                $dsn .= 'dbname=' . $params['host'];

                if (isset($params['port'])) {
                    $dsn .= '/' . $params['port'];
                }

                $dsn .= ':' . $params['dbname'];
            } else {
                $dsn .= 'dbname=' . $params['dbname'];
            }
        }

        // Add charset if specified
        if (isset($params['charset'])) {
            $dsn .= ';charset=' . $params['charset'];
        }

        // Add role if specified
        if (isset($params['role'])) {
            $dsn .= ';role=' . $params['role'];
        }

        // Add dialect if specified
        if (isset($params['dialect'])) {
            $dsn .= ';dialect=' . $params['dialect'];
        }

        return $dsn;
    }
}
