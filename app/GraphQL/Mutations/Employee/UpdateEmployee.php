<?php

namespace App\GraphQL\Mutations\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateEmployee
{
    public function resolve($root, array $args): Employee
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $employee = Employee::with('user')->findOrFail($args['id']);
        $user = $employee->user;

        $employeeData = [];
        $userData = [];

        foreach (['first_name', 'last_name', 'phone', 'address', 'salary', 'job_role', 'join_date'] as $field) {
            if (isset($args[$field])) {
                $employeeData[$field] = $args[$field];
            }
        }

        if (isset($args['email']) && $args['email'] !== $user->email) {
            if (User::withTrashed()->where('email', $args['email'])->where('id', '!=', $user->id)->exists()) {
                throw new \GraphQL\Error\Error('An employee with this email address already exists.');
            }
            $userData['email'] = $args['email'];
        }

        if (isset($args['system_role'])) {
            $userData['system_role'] = $args['system_role'];
        }

        if (! empty($args['password'])) {
            $userData['password'] = Hash::make($args['password']);
        }

        DB::transaction(function () use ($employee, $user, $employeeData, $userData) {
            if (! empty($employeeData)) {
                $employee->update($employeeData);
            }
            if (! empty($userData)) {
                $user->update($userData);
            }
        });

        return $employee->fresh(['user']);
    }
}
