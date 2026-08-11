<?php

namespace App\GraphQL\Queries;

use App\Models\Employee;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Auth;

class EmployeeQuery
{
    public function resolve($root, array $args): Employee
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new Error('This action is unauthorized.');
        }

        $employee = Employee::with('user')->find($args['id']);

        if (! $employee) {
            throw new Error('Employee not found.');
        }

        return $employee;
    }
}
