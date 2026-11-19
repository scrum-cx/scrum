<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data. Safe to run more than once. Every demo account's password is "password".
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->user('Ayesha Admin', 'admin@example.com', Role::Admin);
        $this->user('Mahin Owner', 'owner@example.com');
        $this->user('Nusrat Manager', 'manager@example.com');
        $this->user('Test User', 'test@example.com');
        $this->user('Rafi Developer', 'dev@example.com');
    }

    private function user(string $name, string $email, Role $role = Role::Member): User
    {
        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->fill(['name' => $name, 'password' => Hash::make('password')]);
            $user->email_verified_at = now();
        }
        $user->role = $role;   // role is not mass-assignable on purpose
        $user->save();

        return $user;
    }
}
