<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTransferTask extends Model
{
    use HasUuids;

    protected $attributes = [
        'status' => 'pending',
        'success_count' => 0,
        'errors' => '[]',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'input_path',
        'output_path',
        'success_count',
        'errors',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
