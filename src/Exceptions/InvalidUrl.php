<?php

namespace Spatie\SslCertificate\Exceptions;

use Exception;

class InvalidUrl extends Exception
{
    public static function couldNotValidate(string $url): static
    {
        return new static("String `{$url}` is not a valid url.");
    }

    public static function couldNotDetermineHost(string $url): static
    {
        return new static("Could not determine host from url `{$url}`.");
    }
}
