<?php

namespace App\Services\DocumentGenerator;

use App\Services\DocumentGenerator\Documents\CustomerPriceListDocument;
use InvalidArgumentException;

/**
 * Registry and entry point for the Kabba Document Generation Framework.
 * New document types register here; callers ask for a document by key and
 * render it — presentation stays inside the framework.
 */
class DocumentGenerator
{
    /** @var array<string, class-string<AbstractDocument>> */
    protected static array $documents = [
        CustomerPriceListDocument::KEY => CustomerPriceListDocument::class,
    ];

    public static function make(string $key): AbstractDocument
    {
        if (!isset(static::$documents[$key])) {
            throw new InvalidArgumentException("Unknown document type [{$key}].");
        }

        return new (static::$documents[$key])();
    }

    /** @return array<string, class-string<AbstractDocument>> */
    public static function available(): array
    {
        return static::$documents;
    }
}
