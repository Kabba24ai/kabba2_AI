<?php

namespace App\Services\Discounts;

use RuntimeException;

/** Domain error for a rejected discount application/reversal (server-authoritative). */
class DiscountException extends RuntimeException
{
}
