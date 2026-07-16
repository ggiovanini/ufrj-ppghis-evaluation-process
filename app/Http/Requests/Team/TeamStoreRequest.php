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

    protected function prepareForValidation(): void
    {
        if ($this->has('roles')) {
            $roles = $this->roles;
            if (is_string($roles)) {
                $roles = explode(',', $roles);
            } elseif (is_array($roles) && count($roles) === 1 && is_string($roles[0]) && str_contains($roles[0], ',')) {
                $roles = explode(',', $roles[0]);
            }

            $this->merge([
                'roles' => array_filter(array_map('trim', (array) $roles)),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('users.manage');
    }
}
