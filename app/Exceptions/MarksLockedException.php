<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MarksLockedException extends HttpException
{
    public function __construct(string $message = 'Results are published and marks are locked.', ?\Exception $previous = null)
    {
        parent::__construct(403, $message, $previous);
    }
}
