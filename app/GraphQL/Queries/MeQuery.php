<?php

namespace App\GraphQL\Queries;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MeQuery
{
    public function resolve($root, array $args): ?User
    {
        return Auth::guard('api')->user();
    }
}
