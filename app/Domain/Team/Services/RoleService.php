<?php

namespace App\Domain\Team\Services;

use App\Domain\Shared\Types\UserRoles;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function translate(array|Collection $roles): array
    {
        if ($roles instanceof Collection) {
            return $this->roles($roles->toArray());
        }

        return $this->roles($roles);
    }

    public function roles(array $roles): array
    {
        $data = [];
        foreach ($roles as $role) {
            $data[] = $this->role($role);
        }
        return $data;
    }

    public function role(array $role): array
    {
        $role = $this->toRole($role['name']);

        return [
            'value' => $role->value,
            'label' => $role->label(),
        ];
    }

    public function toRole(string $role): UserRoles
    {
        return UserRoles::from($role);
    }
}
