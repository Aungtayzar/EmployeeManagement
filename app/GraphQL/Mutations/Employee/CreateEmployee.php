<?php

namespace App\GraphQL\Mutations\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateEmployee
{
    public function resolve($root, array $args): Employee
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $email = $args['email'];

        if (User::withTrashed()->where('email', $email)->exists()) {
            throw new \GraphQL\Error\Error('An employee with this email address already exists.');
        }

        return DB::transaction(function () use ($args, $email) {
            $user = User::create([
                'email' => $email,
                'system_role' => $args['system_role'] ?? 'employee',
                'password' => Hash::make($args['password']),
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'first_name' => $args['first_name'],
                'last_name' => $args['last_name'],
                'phone' => $args['phone'] ?? null,
                'address' => $args['address'] ?? null,
                'salary' => $args['salary'],
                'job_role' => $args['job_role'] ?? null,
                'join_date' => $args['join_date'] ?? null,
            ]);

            $employee->load('user');

            return $employee;
        });
    }
}
