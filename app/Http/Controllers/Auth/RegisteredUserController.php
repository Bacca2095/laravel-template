<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    public function __construct(protected OtpService $otpService)
    {
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = DB::transaction(function () use ($payload) {
            $user = User::create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => Hash::make($payload['password']),
                'otp_enabled' => $payload['otp_enabled'] ?? true,
            ]);

            if ($payload['otp_enabled'] ?? true) {
                $this->otpService->issue($user);
            }

            return $user;
        });

        event(new Registered($user));

        return response()->json([
            'user' => $user,
        ], 201);
    }
}
