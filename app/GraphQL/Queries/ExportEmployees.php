<?php

namespace App\GraphQL\Queries;

use App\Exports\EmployeesExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportEmployees
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $filename = 'employees-' . Str::random(16) . '.xlsx';
        $path = 'exports/' . $filename;

        Excel::store(new EmployeesExport, $path, 'local');

        return [
            'url' => route('download.export', ['filename' => $filename]),
        ];
    }
}
