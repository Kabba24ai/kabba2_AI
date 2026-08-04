<?php

namespace App\Services\GiftCards;

use RuntimeException;

/**
 * A refused gift-card operation, carrying the typed reason it was refused.
 *
 * Mirrors {@see App\Services\Goodwill\GoodwillException}: the reason survives
 * as a value, so a controller can render the operator message, decide whether
 * to offer a retry, and choose an HTTP status without parsing a string.
 */
class GiftCardException extends RuntimeException
{
    private function __construct(
        public readonly GiftCardFailure $failure,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function because(GiftCardFailure $failure): self
    {
        return new self($failure, $failure->message());
    }

    /**
     * The same refusal with extra operator context appended — a balance, an
     * order total — where the bare reason would leave them guessing.
     */
    public static function withDetail(GiftCardFailure $failure, string $detail): self
    {
        return new self($failure, $failure->message().' '.$detail);
    }
}
