<?php

namespace App\Http\Requests\Team;

use App\Domain\Shared\Types\UserRoles;
use App\Domain\Team\Exceptions\YouCantRemoveYourselfException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeamUpdateRequest extends FormRequest
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
        $user = $this->user();
        $currentUser = $this->route('user');
        if ($user->id === $currentUser->id)
            throw new YouCantRemoveYourselfException(
                "Você não pode atualizar seu próprio usuário por esse caminho."
            );

        return $this->user()->can('users.manage');
    }
}
