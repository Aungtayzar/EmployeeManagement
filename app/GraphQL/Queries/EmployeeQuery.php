<?php

namespace App\GraphQL\Queries;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class EmployeeQuery
{
    public function resolve($root, array $args): Employee
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        return Employee::with('user')->findOrFail($args['id']);
    }
}
