<?php

namespace App\Services\Goodwill;

use RuntimeException;

/**
 * Domain error for a refused Goodwill operation.
 *
 * Deliberately separate from {@see \App\Services\Discounts\DiscountException}:
 * that one means the shared engine refused to price something, this one means
 * the business refused to authorize it. They surface to the operator
 * differently and must never be caught interchangeably.
 *
 * Where a refusal has a known cause it carries a {@see GoodwillFailure}, so a
 * caller can branch on the reason without matching on message text. Model-level
 * integrity guards raise this without one — they describe a programming error,
 * not a business outcome an operator can act on.
 */
class GoodwillException extends RuntimeException
{
    public ?GoodwillFailure $failure = null;

    /** A refusal with a known business cause. */
    public static function because(GoodwillFailure $failure, ?string $detail = null): self
    {
        $message = $failure->message();

        if ($detail !== null && $detail !== '') {
            $message .= ' '.$detail;
        }

        $exception = new self($message);
        $exception->failure = $failure;

        return $exception;
    }
}
