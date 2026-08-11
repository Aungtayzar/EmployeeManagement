<?php

namespace App\Jobs;

use App\Exports\EmployeesExport;
use App\Models\EmployeeTransferTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $taskId) {}

    public function handle(): void
    {
        $task = EmployeeTransferTask::findOrFail($this->taskId);
        $task->update(['status' => 'processing']);

        try {
            $path = "exports/employees-{$task->id}.xlsx";

            Excel::store(new EmployeesExport, $path, 'local');

            $task->update([
                'status' => 'completed',
                'output_path' => $path,
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($task, $exception);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($task = EmployeeTransferTask::find($this->taskId)) {
            $this->markFailed($task, $exception);
        }
    }

    private function markFailed(EmployeeTransferTask $task, Throwable $exception): void
    {
        Log::error('Employee export failed.', ['task_id' => $task->id, 'exception' => $exception]);

        $task->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
