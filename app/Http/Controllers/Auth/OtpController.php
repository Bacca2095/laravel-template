<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function __construct(protected OtpService $otpService)
    {
    }

    public function store(RequestOtpRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $payload = $request->validated();
        $otp = $this->otpService->issue($user, $payload['type'] ?? 'login');

        return response()->json([
            'message' => __('otp.sent'),
            'expires_at' => $otp->expires_at,
        ]);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $payload = $request->validated();

        /** @var User|null $user */
        $user = User::where('email', $payload['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $this->otpService->validate($user, $payload['otp_code'])) {
            throw ValidationException::withMessages([
                'otp_code' => [__('otp.invalid')],
            ]);
        }

        $expiresAt = ($payload['remember'] ?? false)
            ? now()->addWeeks(2)
            : now()->addHours(2);

        $token = $user->createToken('api-token', abilities: ['*'], expiresAt: $expiresAt);

        return response()->json([
            'message' => __('otp.verified'),
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }
}
