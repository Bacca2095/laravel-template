<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use Carbon\CarbonImmutable;

class OtpService
{
    public function __construct(protected int $ttlSeconds)
    {
    }

    public function issue(User $user, string $type = 'login'): OtpCode
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->delete();

        $otp = OtpCode::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => $type,
            'expires_at' => CarbonImmutable::now()->addSeconds($this->ttlSeconds),
        ]);

        $user->notify(new OtpCodeNotification($code, $this->ttlSeconds));

        return $otp;
    }

    public function validate(User $user, string $code, string $type = 'login'): bool
    {
        $otp = OtpCode::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->latest()
            ->first();

        if (! $otp || $otp->hasExpired()) {
            return false;
        }

        $isValid = hash_equals($otp->code, $code);

        if ($isValid) {
            $otp->delete();
        }

        return $isValid;
    }
}
