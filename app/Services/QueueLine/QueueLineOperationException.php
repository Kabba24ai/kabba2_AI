<?php

namespace App\Services\QueueLine;

use InvalidArgumentException;

/**
 * Operational Queue Line rejection with a STABLE machine-readable code for
 * API consumers. Extends InvalidArgumentException so every existing
 * Livewire/service catch keeps working unchanged; the mobile controllers
 * read $errorCode to build structured 422 responses (no message parsing).
 */
class QueueLineOperationException extends InvalidArgumentException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'QUEUE_INVALID_OPERATION',
    ) {
        parent::__construct($message);
    }
}
