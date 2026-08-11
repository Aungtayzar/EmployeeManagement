<?php

namespace App\GraphQL\Queries;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EmployeeQuery
{
    public function resolve($root, array $args): User
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        return User::findOrFail($args['id']);
    }
}
