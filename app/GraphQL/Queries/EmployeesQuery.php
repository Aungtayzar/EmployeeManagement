<?php

namespace App\GraphQL\Queries;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class EmployeesQuery
{
    public function resolve($root, array $args): array
    {
        $authUser = Auth::guard('api')->user();

        if (! $authUser || ! $authUser->isAdmin()) {
            throw new \GraphQL\Error\Error('This action is unauthorized.');
        }

        $query = Employee::with('user');

        if (! empty($args['search'])) {
            $search = $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($args['join_date_from'])) {
            $query->where('join_date', '>=', $args['join_date_from']);
        }

        if (! empty($args['join_date_to'])) {
            $query->where('join_date', '<=', $args['join_date_to']);
        }

        if (! empty($args['salary_min'])) {
            $query->where('salary', '>=', $args['salary_min']);
        }

        if (! empty($args['salary_max'])) {
            $query->where('salary', '<=', $args['salary_max']);
        }

        $paginator = $query->paginate(
            perPage: 15,
            page: $args['page'] ?? 1
        );

        return [
            'data' => $paginator->items(),
            'paginatorInfo' => [
                'count' => $paginator->count(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
