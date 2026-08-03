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
 */
class GoodwillException extends RuntimeException
{
}
