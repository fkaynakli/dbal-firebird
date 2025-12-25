<?php

declare(strict_types=1);

namespace Foodsoft\FirebirdDriver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Exception;
use Foodsoft\FirebirdDriver\Platform\FirebirdPlatform;
use Foodsoft\FirebirdDriver\Schema\FirebirdSchemaManager;

/**
 * Firebird PDO driver for Doctrine DBAL 2.13.
 */
final class FirebirdDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        if (! empty($params['persistent'])) {
            $driverOptions[\PDO::ATTR_PERSISTENT] = true;
        }

        try {
            return new \Doctrine\DBAL\Driver\PDOConnection(
                $this->constructDsn($params),
                $username,
                $password,
                $driverOptions
            );
        } catch (\PDOException $ex) {
            throw Exception::driverException($this, $ex);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new FirebirdPlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn): AbstractSchemaManager
    {
        return new FirebirdSchemaManager($conn, new FirebirdPlatform());
    }

    /**
     * {@inheritdoc}
     * @deprecated
     */
    public function getName()
    {
        return 'pdo_firebird';
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn)
    {
        $params = $conn->getParams();
        if (isset($params['dbname'])) {
            return $params['dbname'];
        }

        // Fallback: ask database for current DB name
        $stmt = $conn->query("SELECT CAST(RDB$GET_CONTEXT('SYSTEM','DB_NAME') AS VARCHAR(255)) FROM RDB$DATABASE");
        $db = $stmt->fetchColumn();
        return $db !== false ? $db : '';
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
