<?php

declare(strict_types=1);

namespace FKaynakli\FirebirdDriver\Exception;

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query;

use function str_contains;

/** @internal */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /**
     * @link https://firebirdsql.org/file/documentation/reference_manuals/fblangref25-en/html/fblangref25-appx02-sqlcodes.html
     */
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        $message = $exception->getMessage();

        // Firebird error codes
        if (str_contains($message, 'violation of FOREIGN KEY constraint')) {
            return new ForeignKeyConstraintViolationException($exception, $query);
        }

        if (str_contains($message, 'violation of PRIMARY or UNIQUE KEY constraint')) {
            return new UniqueConstraintViolationException($exception, $query);
        }

        if (str_contains($message, 'not null constraint')) {
            return new NotNullConstraintViolationException($exception, $query);
        }

        if (str_contains($message, 'Table unknown') || str_contains($message, 'not found')) {
            return new TableNotFoundException($exception, $query);
        }

        if (str_contains($message, 'already exists')) {
            return new TableExistsException($exception, $query);
        }

        if (str_contains($message, 'Column unknown') || str_contains($message, 'Invalid column')) {
            return new InvalidFieldNameException($exception, $query);
        }

        if (str_contains($message, 'Ambiguous column name') || str_contains($message, 'duplicate')) {
            return new NonUniqueFieldNameException($exception, $query);
        }

        if (
            str_contains($message, 'Dynamic SQL Error') ||
            str_contains($message, 'Token unknown') ||
            str_contains($message, 'SQL error code')
        ) {
            return new SyntaxErrorException($exception, $query);
        }

        if (
            str_contains($message, 'connection shutdown') ||
            str_contains($message, 'connection lost') ||
            str_contains($message, 'Error reading data from the connection')
        ) {
            return new ConnectionException($exception, $query);
        }

        return new DriverException($exception, $query);
    }
}
