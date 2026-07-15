<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('admin');

        $reviewers = User::factory(30)->create();
        $reviewers->each(function ($reviewer) {
            $reviewer->assignRole('reviewer');
        });

        $masterCommittee = User::factory()->create([
            'name' => 'Master Committee',
            'email' => 'master@example.com',
            'password' => bcrypt('password'),
        ]);
        $masterCommittee->assignRole('master_committee');

        $doctorateCommittee = User::factory()->create([
            'name' => 'Doctorate Committee',
            'email' => 'doctorate@example.com',
            'password' => bcrypt('password'),
        ]);
        $doctorateCommittee->assignRole('doctorate_committee');
    }
}
