<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private readonly ApiTokenService $apiTokenService) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_MEMBER,
            'is_active' => true,
        ]);

        $token = $this->apiTokenService->issueToken(
            user: $user,
            name: $validated['device_name'] ?? 'mobile',
            abilities: ['*'],
        );

        return response()->json([
            'message' => 'Compte cree avec succes.',
            'token' => $token,
            'user' => $this->serializeUser($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect.',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Compte desactive.',
            ], 403);
        }

        $token = $this->apiTokenService->issueToken(
            user: $user,
            name: $validated['device_name'] ?? 'mobile',
            abilities: ['*'],
        );

        return response()->json([
            'message' => 'Connexion reussie.',
            'token' => $token,
            'user' => $this->serializeUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->serializeUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var ApiToken|null $apiToken */
        $apiToken = $request->attributes->get('apiToken');

        if ($apiToken) {
            $apiToken->delete();
        }

        return response()->json([
            'message' => 'Deconnexion API effectuee.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'job_title' => $user->job_title,
            'phone' => $user->phone,
        ];
    }
}

