<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return User::query();
    }

    public function headings(): array
    {
        return [
            'ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Address',
            'Salary',
            'System Role',
            'Job Role',
            'Created At',
            'Updated At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->first_name,
            $user->last_name,
            $user->email,
            $user->phone,
            $user->address,
            $user->salary,
            $user->system_role,
            $user->job_role,
            $user->created_at,
            $user->updated_at,
        ];
    }
}
