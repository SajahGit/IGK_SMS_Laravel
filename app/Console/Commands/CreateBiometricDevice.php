<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateBiometricDevice extends Command
{
    protected $signature = 'school:biometric-device:create {name}';

    protected $description = 'Create a device credential for signed biometric event ingestion';

    public function handle(): int
    {
        $secret = Str::random(64);
        $device = BiometricDevice::create([
            'name' => $this->argument('name'),
            'shared_secret' => $secret,
            'is_active' => true,
        ]);

        $this->info('Device ID: '.$device->id);
        $this->line('Shared secret (shown once): '.$secret);
        $this->warn('Store the secret securely. Use HMAC-SHA256(timestamp + "." + raw JSON body) in X-Device-Signature.');

        return self::SUCCESS;
    }
}
