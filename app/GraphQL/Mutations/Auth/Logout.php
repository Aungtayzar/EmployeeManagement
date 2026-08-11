<?php

namespace App\GraphQL\Mutations\Auth;

use Illuminate\Support\Facades\Auth;

class Logout
{
    public function resolve($root, array $args): array
    {
        Auth::guard('api')->logout();

        return ['message' => 'Successfully logged out.'];
    }
}
