<?php

namespace App\GraphQL\Mutations\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class DeleteEmployee
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $employee = Employee::with('user')->findOrFail($args['id']);
        $employee->delete();

        if ($employee->user) {
            $employee->user->delete();
        }

        return ['message' => 'Employee removed successfully.'];
    }
}
