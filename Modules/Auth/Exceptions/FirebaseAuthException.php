<?php

namespace Modules\Auth\Exceptions;

use Exception;

class FirebaseAuthException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $statusCode = 400,
    ) {
        parent::__construct($message);
    }
}
