<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Carbon;

class ApiTokenService
{
    /**
     * @param array<int, string> $abilities
     */
    public function issueToken(
        User $user,
        string $name = 'mobile',
        array $abilities = ['*'],
        ?Carbon $expiresAt = null,
    ): string {
        $plainToken = ApiToken::generatePlainTextToken();

        $user->apiTokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plainToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return $plainToken;
    }
}

