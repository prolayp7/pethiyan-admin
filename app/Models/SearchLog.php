<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $fillable = [
        'query',
        'result_count',
        'entity_types',
        'user_id',
        'session_id',
        'ip_address',
    ];

    protected $casts = [
        'entity_types' => 'array',
        'result_count' => 'integer',
    ];
}
