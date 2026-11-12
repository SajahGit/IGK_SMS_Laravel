<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BiometricEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $device = BiometricDevice::query()->whereKey($request->header('X-Device-ID'))->where('is_active', true)->firstOrFail();
        $timestamp = (string) $request->header('X-Device-Timestamp');
        $signature = (string) $request->header('X-Device-Signature');
        abort_unless(ctype_digit($timestamp) && abs(now()->timestamp - (int) $timestamp) <= 300, 401, 'Device timestamp is invalid or expired.');

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $device->shared_secret);
        abort_unless($signature !== '' && hash_equals($expected, strtolower($signature)), 401, 'Device signature is invalid.');

        $data = $request->validate([
            'event_id' => ['required', 'string', 'max:100'],
            'admission_no' => ['required', 'string', 'max:40'],
            'scanned_at' => ['required', 'date'],
        ]);
        $student = Student::query()->where('admission_no', $data['admission_no'])->where('status', 'active')->first();
        $event = DB::transaction(function () use ($device, $data, $student) {
            $event = $device->events()->firstOrCreate(
                ['event_id' => $data['event_id']],
                [
                    'admission_no' => $data['admission_no'],
                    'student_id' => $student?->id,
                    'scanned_at' => $data['scanned_at'],
                    'payload' => $data,
                    'status' => $student ? 'pending' : 'unmatched',
                ]
            );
            $device->forceFill(['last_seen_at' => now()])->save();

            return $event;
        });

        return response()->json([
            'accepted' => true,
            'duplicate' => ! $event->wasRecentlyCreated,
            'event_id' => $event->event_id,
            'status' => $event->status,
        ], $event->wasRecentlyCreated ? 202 : 200);
    }
}
