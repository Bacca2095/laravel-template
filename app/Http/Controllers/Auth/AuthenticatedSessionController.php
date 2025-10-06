<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected OtpService $otpService)
    {
    }

    public function store(LoginRequest $request): JsonResponse
    {
        $payload = $request->validated();

        /** @var User|null $user */
        $user = User::where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->otp_enabled && empty($payload['otp_code'] ?? null)) {
            $otp = $this->otpService->issue($user);

            return response()->json([
                'requires_otp' => true,
                'expires_at' => $otp->expires_at,
            ], 202);
        }

        if ($user->otp_enabled && ! $this->otpService->validate($user, $payload['otp_code'] ?? '')) {
            throw ValidationException::withMessages([
                'otp_code' => [__('otp.invalid')],
            ]);
        }

        $expiresAt = ($payload['remember'] ?? false)
            ? now()->addWeeks(2)
            : now()->addHours(2);

        $token = $user->createToken('api-token', abilities: ['*'], expiresAt: $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }

    public function destroy(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->currentAccessToken()?->delete();

        return response()->json([], 204);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => Auth::user(),
        ]);
    }
}
