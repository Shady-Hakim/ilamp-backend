<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'benefits' => 'array',
            'process_steps' => 'array',
            'faq_items' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
