<?php

namespace Database\Seeders;

use App\Models\BiometricDevice;
use App\Models\Invoice;
use App\Models\PayHereTransaction;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ExtendedScopeDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Extended-scope demo data may only be seeded outside production.');
        }

        $this->seedPayHereTransaction();
        $this->seedBiometricEvents();
        $this->seedExpiredApiToken();
    }

    private function seedPayHereTransaction(): void
    {
        if (PayHereTransaction::query()->where('order_id', 'like', 'IGK-DEMO-PAYHERE-%')->exists()) {
            return;
        }

        $invoice = Invoice::query()
            ->where('currency', strtoupper((string) config('services.payhere.currency', 'LKR')))
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('balance', '>', 0)
            ->whereNotIn('id', PayHereTransaction::query()->where('status', 'pending')->pluck('invoice_id'))
            ->whereHas('student.user', fn ($query) => $query->where('email', 'student@school.test'))
            ->orderBy('id')
            ->first();

        if (! $invoice) {
            throw new RuntimeException('Sample PayHere data requires an unpaid LKR invoice linked to the demo student.');
        }

        PayHereTransaction::firstOrCreate(
            ['order_id' => 'IGK-DEMO-PAYHERE-'.$invoice->id],
            [
                'invoice_id' => $invoice->id,
                'amount' => $invoice->balance,
                'currency' => $invoice->currency,
                'status' => 'pending',
            ]
        );
    }

    private function seedBiometricEvents(): void
    {
        $student = Student::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'student@school.test'))
            ->firstOrFail();
        $device = BiometricDevice::firstOrCreate(
            ['name' => 'IGK Main Campus North Gate Reader'],
            [
                'shared_secret' => hash('sha256', 'local-only-biometric-demo-device'),
                'is_active' => true,
                'last_seen_at' => now(),
            ]
        );
        $scannedAt = today()->setTime(7, 42);

        $device->events()->firstOrCreate(
            ['event_id' => 'IGK-DEMO-ENTRY-001'],
            [
                'admission_no' => $student->admission_no,
                'student_id' => $student->id,
                'scanned_at' => $scannedAt,
                'payload' => [
                    'event_id' => 'IGK-DEMO-ENTRY-001',
                    'admission_no' => $student->admission_no,
                    'scanned_at' => $scannedAt->toISOString(),
                ],
                'status' => 'pending',
            ]
        );
        $device->events()->firstOrCreate(
            ['event_id' => 'IGK-DEMO-UNMATCHED-001'],
            [
                'admission_no' => 'IGK-UNMATCHED-DEMO',
                'student_id' => null,
                'scanned_at' => $scannedAt->copy()->addMinutes(3),
                'payload' => [
                    'event_id' => 'IGK-DEMO-UNMATCHED-001',
                    'admission_no' => 'IGK-UNMATCHED-DEMO',
                    'scanned_at' => $scannedAt->copy()->addMinutes(3)->toISOString(),
                ],
                'status' => 'unmatched',
            ]
        );
    }

    private function seedExpiredApiToken(): void
    {
        $student = User::query()->where('email', 'student@school.test')->firstOrFail();
        if ($student->tokens()->where('name', 'Expired international student API sample')->exists()) {
            return;
        }

        $student->createToken(
            'Expired international student API sample',
            ['profile:read', 'academic:read'],
            now()->subDay()
        );
    }
}
