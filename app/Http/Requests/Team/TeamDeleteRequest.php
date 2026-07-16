<?php

namespace App\Http\Requests\Team;

use App\Domain\Team\Exceptions\YouCantRemoveYourselfException;
use Illuminate\Foundation\Http\FormRequest;

class TeamDeleteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:254'],
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        $currentUser = $this->route('user');
        if ($user->id === $currentUser->id) {
            throw new YouCantRemoveYourselfException(
                'Você não pode remover seu próprio usuário por esse caminho.'
            );
        }

        return $this->user()->can('users.manage');
    }
}
