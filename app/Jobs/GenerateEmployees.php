<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateEmployees implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $count
    ) {}

    public function handle(): void
    {
        $chunkSize = 500;
        $chunks = ceil($this->count / $chunkSize);

        for ($i = 0; $i < $chunks; $i++) {
            $currentChunk = min($chunkSize, $this->count - ($i * $chunkSize));
            $users = [];

            for ($j = 0; $j < $currentChunk; $j++) {
                $users[] = [
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => fake()->unique()->safeEmail(),
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'phone' => fake()->phoneNumber(),
                    'address' => fake()->address(),
                    'salary' => fake()->randomFloat(2, 30000, 150000),
                    'system_role' => 'employee',
                    'job_role' => fake()->jobTitle(),
                    'remember_token' => Str::random(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            User::insert($users);
        }
    }
}
