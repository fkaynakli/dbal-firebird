<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\PDO\Firebird;

use Doctrine\DBAL\Driver\AbstractFirebirdDriver;
use Doctrine\DBAL\Driver\PDO\Connection as PDOConnection;
use Doctrine\DBAL\Driver\PDO\Exception as PDOException;
use Doctrine\DBAL\Driver\PDO\Exception\InvalidConfiguration;
use Doctrine\DBAL\Driver\PDO\PDOConnect;
use PDO;
use SensitiveParameter;

use function is_string;
use function sprintf;

final class Driver extends AbstractFirebirdDriver
{
    use PDOConnect;

    /**
     * {@inheritDoc}
     */
    public function connect(
        #[SensitiveParameter]
        array $params,
    ): Connection {
        $driverOptions = [];

        if (isset($params['driverOptions'])) {
            $driverOptions = $params['driverOptions'];
        }

        if (! empty($params['persistent'])) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        foreach (['user', 'password'] as $key) {
            if (isset($params[$key]) && ! is_string($params[$key])) {
                throw InvalidConfiguration::notAStringOrNull($key, $params[$key]);
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
            throw PDOException::new($exception);
        }

        return new Connection(new PDOConnection($pdo));
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

        // Add dialect if specified (Firebird 3.0+ supports dialect parameter)
        if (isset($params['dialect'])) {
            $dsn .= ';dialect=' . $params['dialect'];
        }

        return $dsn;
    }
}
