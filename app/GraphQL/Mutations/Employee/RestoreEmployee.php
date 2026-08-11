<?php

namespace App\GraphQL\Mutations\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class RestoreEmployee
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $employee = Employee::withTrashed()->findOrFail($args['id']);
        $employee->restore();

        return ['message' => 'Employee restored successfully.'];
    }
}
