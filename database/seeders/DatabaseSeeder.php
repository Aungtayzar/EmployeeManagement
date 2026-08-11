<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'system_role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $total = 10;
        $chunkSize = 500;

        for ($i = 0; $i < $total; $i += $chunkSize) {
            Employee::factory()->count(min($chunkSize, $total - $i))->create();
        }
    }
}
