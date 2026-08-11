<?php

namespace App\GraphQL\Mutations\Auth;

use Illuminate\Support\Facades\Auth;

class RefreshToken
{
    public function resolve($root, array $args): array
    {
        try {
            $token = Auth::guard('api')->refresh();
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            throw new \GraphQL\Error\Error('The token has been blacklisted. Please log in again.');
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            throw new \GraphQL\Error\Error('The token cannot be refreshed. Please log in again.');
        }

        return ['token' => $token];
    }
}
