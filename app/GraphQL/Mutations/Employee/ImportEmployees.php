<?php

namespace App\GraphQL\Mutations\Employee;

use App\Imports\EmployeesImport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ImportEmployees
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $file = $args['file'];

        $import = new EmployeesImport;
        Excel::import($import, $file);

        $failures = $import->failures();
        $errors = [];

        foreach ($failures as $failure) {
            $errors[] = [
                'row' => $failure->row(),
                'message' => implode(', ', $failure->errors()),
            ];
        }

        return [
            'success_count' => $import->getRowCount() - count($failures),
            'errors' => $errors,
        ];
    }
}
