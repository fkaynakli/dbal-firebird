<?php

declare(strict_types=1);

namespace Foodsoft\FirebirdDriver;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Firebird Driver Bundle for Symfony
 */
class FirebirdDriverBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
