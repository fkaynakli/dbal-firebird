<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\PDO\Firebird;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\PDO\Statement as PDOStatement;
use Doctrine\DBAL\ParameterType;

final class Statement extends AbstractStatementMiddleware
{
    /** @internal The statement can be only instantiated by its driver connection. */
    public function __construct(private readonly PDOStatement $statement)
    {
        parent::__construct($statement);
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        // Firebird specific parameter binding
        // BLOB types need special handling in Firebird
        switch ($type) {
            case ParameterType::LARGE_OBJECT:
            case ParameterType::BINARY:
                // For BLOB data, use STRING parameter type as Firebird PDO handles it automatically
                $this->statement->bindValue($param, $value, ParameterType::STRING);
                break;

            default:
                $this->statement->bindValue($param, $value, $type);
        }
    }
}
