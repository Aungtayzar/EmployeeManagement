<?php

namespace App\GraphQL\Mutations\Employee;

use App\Jobs\GenerateEmployees as GenerateEmployeesJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GenerateEmployees
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $job = new GenerateEmployeesJob($args['count']);
        $uuid = Str::uuid()->toString();

        dispatch($job);

        return ['job_id' => $uuid];
    }
}
