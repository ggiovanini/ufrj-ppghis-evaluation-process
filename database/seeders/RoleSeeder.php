<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate([
            'name' => 'admin',
        ]);
        $reviewer = Role::firstOrCreate([
            'name' => 'reviewer',
        ]);
        $master_committee = Role::firstOrCreate([
            'name' => 'master_committee',
        ]);
        $doctorate_committee = Role::firstOrCreate([
            'name' => 'doctorate_committee',
        ]);

        $admin->syncPermissions([
            'projects.import',
            'projects.view',
            'projects.manage',
            'projects.distribute',
            'review.assign',
            'review.results.view',
            'review.results.calculate',
            'written-exam.record',
            'results.view',
            'results.publish',
            'users.manage',
        ]);

        $reviewer->syncPermissions([
            'review.evaluate',
            'review.submit',
            'review.update',
            'review.view-own',
        ]);

        $master_committee->syncPermissions([
            'review.results.view',
            'committee.evaluate',
            'committee.submit',
            'committee.update',
            'committee.results.view',
        ]);

        $doctorate_committee->syncPermissions([
            'review.results.view',
            'committee.evaluate',
            'committee.submit',
            'committee.update',
            'committee.results.view',
        ]);
    }
}
