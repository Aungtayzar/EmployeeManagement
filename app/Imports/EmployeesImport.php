<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class EmployeesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
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
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'phone' => $row['phone'] ?? $user->phone,
                    'address' => $row['address'] ?? $user->address,
                    'salary' => $row['salary'] ?? $user->salary,
                    'system_role' => $row['system_role'] ?? $user->system_role,
                    'job_role' => $row['job_role'] ?? $user->job_role,
                ]);
            } else {
                User::create([
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'salary' => $row['salary'] ?? 0,
                    'system_role' => $row['system_role'] ?? 'employee',
                    'job_role' => $row['job_role'] ?? null,
                    'password' => Hash::make('password'),
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
        ];
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
}
