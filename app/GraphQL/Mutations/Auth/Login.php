<?php

namespace App\GraphQL\Mutations\Auth;

use Illuminate\Support\Facades\Auth;

class Login
{
    public function resolve($root, array $args): array
    {
        $credentials = [
            'email' => $args['email'],
            'password' => $args['password'],
        ];

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            throw new \GraphQL\Error\Error('Invalid credentials.');
        }

        return ['token' => $token];
    }
}
