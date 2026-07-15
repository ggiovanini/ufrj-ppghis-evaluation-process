<?php

namespace App\Http\Requests\Team;

use App\Domain\Shared\Types\UserRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeamStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable'],
            'email' => ['nullable', 'email', 'max:254'],
            'password' => ['required', 'string', Password::default()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['required', Rule::enum(UserRoles::class)],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()->can('users.manage');
    }
}
