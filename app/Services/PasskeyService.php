<?php

namespace App\Services;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Validation\ValidationException;

class PasskeyService
{
    protected string $rpId;
    protected string $rpName;
    protected ?string $rpIcon;
    protected int $challengeBytes;
    protected int $timeout;

    public function __construct(protected CacheRepository $cache)
    {
        $config = config('passkeys');

        $this->rpId = data_get($config, 'relying_party.id', 'localhost');
        $this->rpName = data_get($config, 'relying_party.name', 'Laravel API');
        $this->rpIcon = data_get($config, 'relying_party.icon');
        $this->challengeBytes = (int) data_get($config, 'challenge_bytes', 32);
        $this->timeout = (int) data_get($config, 'timeout', 60000);
    }

    public function creationOptions(User $user): array
    {
        $challenge = $this->generateChallenge();
        $this->cacheChallenge("passkey:register:{$user->id}", $challenge);

        return [
            'rp' => [
                'name' => $this->rpName,
                'id' => $this->rpId,
                'icon' => $this->rpIcon,
            ],
            'user' => [
                'id' => base64_encode((string) $user->getAuthIdentifier()),
                'name' => $user->email,
                'displayName' => $user->name ?? $user->email,
            ],
            'challenge' => $challenge,
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'timeout' => $this->timeout,
            'authenticatorSelection' => [
                'userVerification' => 'required',
            ],
            'attestation' => 'none',
            'excludeCredentials' => Passkey::query()
                ->where('user_id', $user->id)
                ->get()
                ->map(fn (Passkey $passkey) => [
                    'type' => 'public-key',
                    'id' => $passkey->credential_id,
                    'transports' => $passkey->transports ?? [],
                ])->values()->all(),
        ];
    }

    public function register(User $user, array $attestation, ?string $name = null): Passkey
    {
        $challenge = $this->pullChallenge("passkey:register:{$user->id}");

        if ($challenge === null || ! hash_equals($challenge, data_get($attestation, 'challenge'))) {
            throw ValidationException::withMessages([
                'attestation' => ['Invalid passkey registration challenge.'],
            ]);
        }

        $credentialId = data_get($attestation, 'credentialId');
        $publicKey = data_get($attestation, 'publicKey');
        $counter = (int) data_get($attestation, 'counter', 0);

        if (! is_string($credentialId) || ! is_string($publicKey)) {
            throw ValidationException::withMessages([
                'attestation' => ['Incomplete passkey attestation payload.'],
            ]);
        }

        $hash = hash('sha256', $credentialId);

        $existing = Passkey::query()->where('credential_hash', $hash)->first();

        if ($existing && $existing->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'attestation' => ['This passkey is already registered with another account.'],
            ]);
        }

        return Passkey::query()->updateOrCreate(
            ['credential_hash' => $hash],
            [
                'user_id' => $user->id,
                'name' => $name,
                'public_key' => $publicKey,
                'credential_id' => $credentialId,
                'credential_hash' => $hash,
                'counter' => $counter,
                'transports' => data_get($attestation, 'transports'),
                'device_type' => data_get($attestation, 'deviceType'),
                'backed_up' => (bool) data_get($attestation, 'backedUp', false),
            ]
        );
    }

    public function requestOptions(?User $user = null, ?string $email = null): array
    {
        $identifier = $user?->id ?? $email;

        if ($identifier === null) {
            throw ValidationException::withMessages([
                'email' => ['A user or email is required.'],
            ]);
        }

        $challenge = $this->generateChallenge();
        $this->cacheChallenge("passkey:login:{$identifier}", $challenge);

        $passkeys = Passkey::query()
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->when(! $user && $email, function ($query) use ($email) {
                $query->whereHas('user', fn ($relation) => $relation->where('email', $email));
            })
            ->get()
            ->map(fn (Passkey $passkey) => [
                'type' => 'public-key',
                'id' => $passkey->credential_id,
                'transports' => $passkey->transports ?? [],
            ])->values()->all();

        return [
            'challenge' => $challenge,
            'timeout' => $this->timeout,
            'rpId' => $this->rpId,
            'allowCredentials' => $passkeys,
            'userVerification' => 'required',
        ];
    }

    public function authenticate(User $user, array $assertion): bool
    {
        $challenge = $this->pullChallenge("passkey:login:{$user->id}")
            ?? $this->pullChallenge("passkey:login:{$user->email}");

        if ($challenge === null || ! hash_equals($challenge, data_get($assertion, 'challenge'))) {
            return false;
        }

        $credentialId = data_get($assertion, 'credentialId');
        $counter = (int) data_get($assertion, 'counter', 0);

        if (! is_string($credentialId) || $credentialId === '') {
            return false;
        }

        $hash = $credentialId ? hash('sha256', $credentialId) : null;

        /** @var Passkey|null $passkey */
        $passkey = Passkey::query()
            ->where('user_id', $user->id)
            ->where('credential_hash', $hash)
            ->first();

        if (! $passkey) {
            return false;
        }

        if ($counter <= $passkey->counter) {
            return false;
        }

        $passkey->update(['counter' => $counter]);

        return true;
    }

    protected function generateChallenge(): string
    {
        return rtrim(strtr(base64_encode(random_bytes($this->challengeBytes)), '+/', '-_'), '=');
    }

    protected function cacheChallenge(string $key, string $challenge): void
    {
        $this->cache->put($key, $challenge, now()->addMilliseconds($this->timeout));
    }

    protected function pullChallenge(string $key): ?string
    {
        $challenge = $this->cache->get($key);

        if ($challenge) {
            $this->cache->forget($key);
        }

        return $challenge;
    }
}
