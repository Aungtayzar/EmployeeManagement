<?php

namespace App\Jobs;

use App\Imports\EmployeesImport;
use App\Models\EmployeeTransferTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $taskId) {}

    public function handle(): void
    {
        $task = EmployeeTransferTask::findOrFail($this->taskId);
        $task->update(['status' => 'processing']);

        try {
            $import = new EmployeesImport;
            Excel::import($import, $task->input_path, 'local');

            $errors = array_map(static fn ($failure) => [
                'row' => $failure->row(),
                'message' => implode(', ', $failure->errors()),
            ], $import->failures());

            $task->update([
                'status' => 'completed',
                // getRowCount() only counts rows that passed validation and reached
                // collection(); failed rows are skipped by SkipsOnFailure.
                'success_count' => $import->getRowCount(),
                'errors' => $errors,
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
        Log::error('Employee import failed.', ['task_id' => $task->id, 'exception' => $exception]);

        $task->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
