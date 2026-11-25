<?php

namespace App\Services;

use App\Mail\PasswordChangedConfirmationMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PasswordResetOtpService
{
    public function isEligibleForSelfReset(?User $user): bool
    {
        return $user !== null
            && $user->is_active
            && ($user->isSuperAdmin() || $user->isInstitutionAdmin());
    }

    public function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * Generate and send a 6-digit OTP for eligible admin accounts.
     *
     * @return array{status: string, cooldown_remaining?: int}
     */
    public function sendOtp(string $rawEmail, ?string $ipAddress = null): array
    {
        $email = $this->normalizeEmail($rawEmail);
        $user = User::query()->where('email', $email)->first();

        if (! $this->isEligibleForSelfReset($user)) {
            return ['status' => 'ineligible_or_unknown'];
        }

        $cooldownRemaining = $this->getResendCooldownRemaining($email);
        if ($cooldownRemaining > 0) {
            return [
                'status' => 'cooldown',
                'cooldown_remaining' => $cooldownRemaining,
            ];
        }

        $otp = (string) random_int(100000, 999999);

        $record = DB::transaction(function () use ($user, $email, $otp, $ipAddress): PasswordResetOtp {
            PasswordResetOtp::query()
                ->where('user_id', $user->id)
                ->where('purpose', PasswordResetOtp::PURPOSE_PASSWORD_RESET)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            return PasswordResetOtp::create([
                'user_id' => $user->id,
                'email' => $email,
                'purpose' => PasswordResetOtp::PURPOSE_PASSWORD_RESET,
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(PasswordResetOtp::OTP_EXPIRY_MINUTES),
                'last_sent_at' => now(),
                'ip_address' => $ipAddress,
            ]);
        });

        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail(
                $user->name,
                $otp,
                PasswordResetOtp::OTP_EXPIRY_MINUTES
            ));
        } catch (\Throwable $e) {
            $record->forceFill(['used_at' => now()])->save();

            Log::error('Failed to deliver password reset OTP email.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $isNetworkError = str_contains($e->getMessage(), 'getaddrinfo')
                || str_contains($e->getMessage(), 'Connection could not be established');

            return [
                'status' => 'mail_failed',
                'reason' => $isNetworkError ? 'network' : 'smtp',
            ];
        }

        AuditLogger::log('password_reset_otp_sent', $user, null, [
            'email' => $user->email,
            'ip_address' => $ipAddress,
        ]);

        return ['status' => 'sent'];
    }

    public function getResendCooldownRemaining(string $rawEmail): int
    {
        $email = $this->normalizeEmail($rawEmail);
        $latest = PasswordResetOtp::query()
            ->where('email', $email)
            ->where('purpose', PasswordResetOtp::PURPOSE_PASSWORD_RESET)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $latest || ! $latest->last_sent_at) {
            return 0;
        }

        $elapsed = (int) $latest->last_sent_at->diffInSeconds(now());
        $remaining = PasswordResetOtp::RESEND_COOLDOWN_SECONDS - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Verify a 6-digit OTP and issue a short-lived single-use reset authorization token.
     *
     * @return array{valid: bool, token?: string, reason?: string}
     */
    public function verifyOtp(string $rawEmail, string $rawOtp): array
    {
        $email = $this->normalizeEmail($rawEmail);
        $otp = trim($rawOtp);

        return DB::transaction(function () use ($email, $otp): array {
            $record = PasswordResetOtp::query()
                ->where('email', $email)
                ->where('purpose', PasswordResetOtp::PURPOSE_PASSWORD_RESET)
                ->whereNull('used_at')
                ->whereNull('reset_token_hash')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return ['valid' => false, 'reason' => 'invalid_or_expired'];
            }

            if ($record->isExpired()) {
                $record->forceFill(['used_at' => now()])->save();

                return ['valid' => false, 'reason' => 'expired'];
            }

            if ($record->hasExceededAttempts()) {
                $record->forceFill(['used_at' => now()])->save();

                return ['valid' => false, 'reason' => 'max_attempts'];
            }

            if (! Hash::check($otp, $record->otp_hash)) {
                $nextAttempts = $record->attempts + 1;
                $updates = ['attempts' => $nextAttempts];
                if ($nextAttempts >= PasswordResetOtp::MAX_ATTEMPTS) {
                    $updates['used_at'] = now();
                }
                $record->forceFill($updates)->save();

                return [
                    'valid' => false,
                    'reason' => $nextAttempts >= PasswordResetOtp::MAX_ATTEMPTS ? 'max_attempts' : 'incorrect',
                ];
            }

            $user = User::query()->find($record->user_id);
            if (! $this->isEligibleForSelfReset($user)) {
                $record->forceFill(['used_at' => now()])->save();

                return ['valid' => false, 'reason' => 'ineligible'];
            }

            $plainToken = Str::random(64);
            $record->forceFill([
                'otp_hash' => Hash::make(Str::random(32)),
                'reset_token_hash' => hash('sha256', $plainToken),
                'reset_token_expires_at' => now()->addMinutes(PasswordResetOtp::RESET_TOKEN_EXPIRY_MINUTES),
            ])->save();

            AuditLogger::log('password_reset_otp_verified', $user, null, ['email' => $user->email]);

            return ['valid' => true, 'token' => $plainToken];
        });
    }

    public function hasValidResetToken(string $rawEmail, string $plainToken): bool
    {
        if ($plainToken === '') {
            return false;
        }

        $email = $this->normalizeEmail($rawEmail);
        $record = PasswordResetOtp::query()
            ->where('email', $email)
            ->where('purpose', PasswordResetOtp::PURPOSE_PASSWORD_RESET)
            ->where('reset_token_hash', hash('sha256', $plainToken))
            ->whereNull('used_at')
            ->first();

        return $record !== null && $record->isResetTokenValid();
    }

    /**
     * Complete the password reset using a verified single-use reset token.
     *
     * @return array{reset: bool, reason?: string, confirmation_sent?: bool}
     */
    public function resetPassword(string $rawEmail, string $plainToken, string $newPassword, ?string $ipAddress = null): array
    {
        $email = $this->normalizeEmail($rawEmail);
        $tokenHash = hash('sha256', $plainToken);

        $outcome = DB::transaction(function () use ($email, $tokenHash, $newPassword, $ipAddress): array {
            $record = PasswordResetOtp::query()
                ->where('email', $email)
                ->where('purpose', PasswordResetOtp::PURPOSE_PASSWORD_RESET)
                ->where('reset_token_hash', $tokenHash)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->first();

            if (! $record || ! $record->isResetTokenValid()) {
                return ['reset' => false, 'reason' => 'invalid_or_expired_token'];
            }

            $user = User::query()->lockForUpdate()->find($record->user_id);
            if (! $this->isEligibleForSelfReset($user)) {
                $record->forceFill(['used_at' => now()])->save();

                return ['reset' => false, 'reason' => 'ineligible'];
            }

            // Preserve all 2FA columns; update only password, remember_token, must_change_password
            $user->forceFill([
                'password' => $newPassword,
                'remember_token' => Str::random(60),
                'must_change_password' => false,
            ])->save();

            // Invalidate all outstanding OTPs and reset tokens for this user
            PasswordResetOtp::query()
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->update([
                    'used_at' => now(),
                    'reset_token_hash' => null,
                ]);

            // Revoke database sessions and API tokens if tables exist
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->where('tokenable_id', $user->id)
                    ->delete();
            }

            AuditLogger::log('password_reset', $user, null, [
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'two_factor_preserved' => $user->two_factor_enabled_at !== null,
            ]);

            return ['reset' => true, 'user' => $user];
        });

        if (! $outcome['reset']) {
            return $outcome;
        }

        /** @var User $user */
        $user = $outcome['user'];
        $confirmationSent = true;

        try {
            Mail::to($user->email)->send(new PasswordChangedConfirmationMail(
                $user->name,
                now()->format('d M Y, h:i A T'),
                $ipAddress
            ));
        } catch (\Throwable $e) {
            $confirmationSent = false;
            Log::warning('Password was reset, but confirmation email delivery failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'reset' => true,
            'confirmation_sent' => $confirmationSent,
        ];
    }
}
