<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileDeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileDeviceController extends Controller
{
    public function registerToken(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'device_token' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', Rule::in(['fcm'])],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        MobileDeviceToken::query()->updateOrCreate(
            ['device_token' => $validated['device_token']],
            [
                'user_id' => $user->id,
                'provider' => $validated['provider'] ?? 'fcm',
                'device_name' => $validated['device_name'] ?? 'mobile',
                'last_used_at' => now(),
                'revoked_at' => null,
            ],
        );

        return response()->json([
            'message' => 'Token mobile enregistre.',
        ], 201);
    }

    public function revokeToken(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'device_token' => ['required', 'string', 'max:255'],
        ]);

        $deviceToken = $user->mobileDeviceTokens()
            ->where('device_token', $validated['device_token'])
            ->first();

        if ($deviceToken) {
            $deviceToken->update(['revoked_at' => now()]);
        }

        return response()->json([
            'message' => 'Token mobile revoque.',
        ]);
    }
}

