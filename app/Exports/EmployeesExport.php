<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Employee::with('user');
    }

    public function headings(): array
    {
        return [
            'ID',
            'User ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Address',
            'Salary',
            'System Role',
            'Job Role',
            'Join Date',
            'Created At',
            'Updated At',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->id,
            $employee->user_id,
            $employee->first_name,
            $employee->last_name,
            $employee->user?->email,
            $employee->phone,
            $employee->address,
            $employee->salary,
            $employee->user?->system_role,
            $employee->job_role,
            $employee->join_date,
            $employee->created_at,
            $employee->updated_at,
        ];
    }
}
