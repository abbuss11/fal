<?php

namespace App\Http\Requests\Client;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', Rule::in([User::ROLE_PROJECT_MANAGER, User::ROLE_MEMBER])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

