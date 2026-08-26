<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class SupplierRequestException extends RuntimeException
{
    public static function forSupplier(string $supplierSlug, Throwable $previous): self
    {
        return new self(
            "Supplier request failed for [{$supplierSlug}]: {$previous->getMessage()}",
            (int) $previous->getCode(),
            $previous
        );
    }
}
