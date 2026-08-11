<?php

namespace App\GraphQL\Mutations\Employee;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UpdateEmployee
{
    public function resolve($root, array $args): User
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $user = User::findOrFail($args['id']);
        $data = [];

        foreach (['first_name', 'last_name', 'email', 'phone', 'address', 'salary', 'system_role', 'job_role'] as $field) {
            if (isset($args[$field])) {
                $data[$field] = $args[$field];
            }
        }

        if (! empty($data['email']) && $data['email'] !== $user->email) {
            if (User::withTrashed()->where('email', $data['email'])->where('id', '!=', $user->id)->exists()) {
                throw new \GraphQL\Error\Error('An employee with this email address already exists.');
            }
        }

        if (! empty($args['password'])) {
            $data['password'] = Hash::make($args['password']);
        }

        $user->update($data);

        return $user->fresh();
    }
}
