<?php

namespace App\Console\Commands;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Signature('permissions:sync')]
#[Description('Sincroniza todas as permissões e papéis do sistema e limpa o cache de permissões')]
class SyncPermissionsCommand extends Command
{
    public function handle(): int
    {
        $this->info('Sincronizando permissões do sistema...');

        app(PermissionSeeder::class)->run();
        app(RoleSeeder::class)->run();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = Role::with('permissions')->orderBy('name')->get();

        $tableRows = $roles->map(fn (Role $role) => [
            $role->name,
            $role->guard_name,
            $role->permissions->count(),
        ])->all();

        $this->table(['Papel', 'Guard', 'Permissões'], $tableRows);

        $this->info('Permissões e papéis sincronizados com sucesso!');

        return self::SUCCESS;
    }
}
