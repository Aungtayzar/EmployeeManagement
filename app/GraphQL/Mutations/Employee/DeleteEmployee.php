<?php

namespace App\GraphQL\Mutations\Employee;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DeleteEmployee
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $user = User::findOrFail($args['id']);
        $user->delete();

        return ['message' => 'Employee removed successfully.'];
    }
}
