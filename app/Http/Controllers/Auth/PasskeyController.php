<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasskeyAuthenticationRequest;
use App\Http\Requests\PasskeyChallengeRequest;
use App\Http\Requests\PasskeyRegistrationRequest;
use App\Models\User;
use App\Services\PasskeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PasskeyController extends Controller
{
    public function __construct(protected PasskeyService $passkeyService)
    {
    }

    public function options(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json($this->passkeyService->creationOptions($user));
    }

    public function register(PasskeyRegistrationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $payload = $request->validated();

        $passkey = $this->passkeyService->register($user, $payload['attestation'], $payload['name'] ?? null);

        $user->update(['passkey_enabled' => true]);

        return response()->json([
            'passkey' => $passkey,
        ], 201);
    }

    public function authenticationOptions(PasskeyChallengeRequest $request): JsonResponse
    {
        $payload = $request->validated();

        /** @var User|null $user */
        $user = User::where('email', $payload['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return response()->json($this->passkeyService->requestOptions($user, $payload['email']));
    }

    public function authenticate(PasskeyAuthenticationRequest $request): JsonResponse
    {
        $payload = $request->validated();

        /** @var User|null $user */
        $user = User::where('email', $payload['email'])->first();

        if (! $user || ! $this->passkeyService->authenticate($user, $payload['assertion'])) {
            throw ValidationException::withMessages([
                'assertion' => ['Invalid passkey assertion.'],
            ]);
        }

        $token = $user->createToken('api-token', abilities: ['*'], expiresAt: now()->addHours(2));

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }
}
