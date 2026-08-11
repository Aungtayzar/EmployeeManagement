<?php

namespace App\GraphQL\Mutations\Employee;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RestoreEmployee
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $user = User::withTrashed()->findOrFail($args['id']);
        $user->restore();

        return ['message' => 'Employee restored successfully.'];
    }
}
