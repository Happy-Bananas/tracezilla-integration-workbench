<?php

namespace App\Clients\Exceptions;

use RuntimeException;

class ClientConfigurationException extends RuntimeException
{
    public static function missing(string $key): self
    {
        return new self("Missing required client configuration [{$key}].");
    }

    public static function invalidPositiveInteger(string $key): self
    {
        return new self("Client configuration [{$key}] must be a positive integer.");
    }
}
