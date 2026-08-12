<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class EmployeesImport implements SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow, WithValidation
{
    protected int $rowCount = 0;

    /**
     * @var Failure[]
     */
    protected array $failures = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowCount++;

            $user = User::withTrashed()->where('email', $row['email'])->first();

            if ($user) {
                $user->update([
                    'system_role' => $row['system_role'] ?? $user->system_role,
                ]);

                $employee = Employee::where('user_id', $user->id)->first();

                if ($employee) {
                    $employee->update([
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'phone' => $row['phone'] ?? $employee->phone,
                        'address' => $row['address'] ?? $employee->address,
                        'salary' => $row['salary'] ?? $employee->salary,
                        'job_role' => $row['job_role'] ?? $employee->job_role,
                        'join_date' => $row['join_date'] ?? $employee->join_date,
                    ]);
                } else {
                    Employee::create([
                        'user_id' => $user->id,
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'phone' => $row['phone'] ?? null,
                        'address' => $row['address'] ?? null,
                        'salary' => $row['salary'] ?? 0,
                        'job_role' => $row['job_role'] ?? null,
                        'join_date' => $row['join_date'] ?? null,
                    ]);
                }
            } else {
                $user = User::create([
                    'email' => $row['email'],
                    'password' => Hash::make('password'),
                    'system_role' => $row['system_role'] ?? 'employee',
                ]);

                Employee::create([
                    'user_id' => $user->id,
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'salary' => $row['salary'] ?? 0,
                    'job_role' => $row['job_role'] ?? null,
                    'join_date' => $row['join_date'] ?? null,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'system_role' => ['nullable', 'in:admin,employee'],
            'join_date' => ['nullable', 'date'],
        ];
    }

    public function prepareForValidation(array $row): array
    {
        $row['join_date'] = $this->normalizeJoinDate($row['join_date'] ?? null);

        return $row;
    }

    /**
     * Normalize join_date to 'Y-m-d' regardless of how Excel stored it:
     * real date cells arrive as raw serial numbers (e.g. 45881), text cells
     * as strings ('2024-02-01', '8/12/2026', '2026-08-12 00:00:00').
     */
    private function normalizeJoinDate(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            // Leave untouched so the 'date' rule reports it as a row failure.
            return $value;
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = $failure;
        }
    }

    public function failures(): array
    {
        return $this->failures;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
