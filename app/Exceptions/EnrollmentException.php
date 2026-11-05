<?php

namespace App\Exceptions;

use Exception;

class EnrollmentException extends Exception
{
    public function __construct(string $message = 'Enrollment failed.', int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
