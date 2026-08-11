<?php

namespace App\GraphQL\Mutations\Employee;

use App\GraphQL\Queries\EmployeeTransferTaskQuery;
use App\Jobs\ImportEmployeesJob;
use App\Models\EmployeeTransferTask;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Auth;

class ImportEmployees
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new Error('This action is unauthorized.');
        }

        $path = $args['file']->store('imports', 'local');

        $task = EmployeeTransferTask::create([
            'user_id' => $authUser->id,
            'type' => 'import',
            'status' => 'pending',
            'input_path' => $path,
        ]);

        ImportEmployeesJob::dispatch($task->id);

        return EmployeeTransferTaskQuery::payload($task);
    }
}
