<?php

namespace App\Services;

use RuntimeException;

class NvidiaImageRequestException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $model = null,
    ) {
        parent::__construct($message);
    }
}
