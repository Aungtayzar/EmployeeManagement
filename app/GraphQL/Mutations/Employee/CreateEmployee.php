<?php

namespace App\GraphQL\Mutations\Employee;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CreateEmployee
{
    public function resolve($root, array $args): User
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $email = $args['email'];

        if (User::withTrashed()->where('email', $email)->exists()) {
            throw new \GraphQL\Error\Error('An employee with this email address already exists.');
        }

        return User::create([
            'first_name' => $args['first_name'],
            'last_name' => $args['last_name'],
            'email' => $email,
            'phone' => $args['phone'] ?? null,
            'address' => $args['address'] ?? null,
            'salary' => $args['salary'],
            'system_role' => $args['system_role'] ?? 'employee',
            'job_role' => $args['job_role'] ?? null,
            'password' => Hash::make($args['password']),
        ]);
    }
}
