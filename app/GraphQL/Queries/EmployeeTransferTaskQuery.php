<?php

namespace App\GraphQL\Queries;

use App\Models\EmployeeTransferTask;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Auth;

class EmployeeTransferTaskQuery
{
    public function resolve($root, array $args): array
    {
        $user = Auth::guard('api')->user();

        if (! $user || ! $user->isAdmin()) {
            throw new Error('This action is unauthorized.');
        }

        $task = EmployeeTransferTask::whereKey($args['id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        return $this->payload($task);
    }

    public static function payload(EmployeeTransferTask $task): array
    {
        return [
            'id' => $task->id,
            'type' => $task->type,
            'status' => $task->status,
            'success_count' => $task->success_count ?? 0,
            'errors' => $task->errors ?? [],
            'error_message' => $task->error_message,
            'url' => $task->output_path
                ? route('download.export', ['filename' => basename($task->output_path)])
                : null,
        ];
    }
}
