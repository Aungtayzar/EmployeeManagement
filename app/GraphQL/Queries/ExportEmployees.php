<?php

namespace App\GraphQL\Queries;

use App\Jobs\ExportEmployeesJob;
use App\Models\EmployeeTransferTask;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Auth;

class ExportEmployees
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new Error('This action is unauthorized.');
        }

        $task = EmployeeTransferTask::create([
            'user_id' => $authUser->id,
            'type' => 'export',
            'status' => 'pending',
        ]);

        ExportEmployeesJob::dispatch($task->id);

        return EmployeeTransferTaskQuery::payload($task);
    }
}
