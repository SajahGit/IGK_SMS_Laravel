<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ConfiguredHttpSmsGateway implements SmsGateway
{
    public function send(string $to, string $message, ?string $reference = null): void
    {
        $endpoint = (string) config('services.sms.endpoint');
        $token = (string) config('services.sms.token');
        if (! config('services.sms.enabled') || $endpoint === '' || $token === '') {
            throw new RuntimeException('SMS delivery is disabled or not configured.');
        }

        Http::acceptJson()
            ->withToken($token)
            ->timeout((int) config('services.sms.timeout', 10))
            ->post($endpoint, [
                'to' => $to,
                'message' => $message,
                'sender_id' => config('services.sms.sender_id'),
                'reference' => $reference,
            ])
            ->throw();
    }
}
