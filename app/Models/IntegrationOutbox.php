<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationOutbox extends Model
{
    protected $table = 'integration_outbox';

    protected $fillable = [
        'entity',
        'mysql_id',
        'action',
        'payload',
        'attempts',
        'last_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
