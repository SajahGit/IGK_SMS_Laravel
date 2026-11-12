<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SmsService
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function send(string $to, string $message, ?string $reference = null): void
    {
        $to = preg_replace('/[\s().-]+/', '', trim($to)) ?? '';
        $message = trim($message);
        if (preg_match('/^\+[1-9]\d{7,14}$/', $to) !== 1) {
            throw ValidationException::withMessages(['phone' => 'Phone number must use international E.164 format.']);
        }
        if ($message === '' || Str::length($message) > 1600) {
            throw ValidationException::withMessages(['message' => 'SMS message must contain between 1 and 1600 characters.']);
        }

        $this->gateway->send($to, $message, $reference);
    }
}
