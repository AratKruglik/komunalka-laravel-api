<?php

namespace Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    protected $fillable = [
        'source_path',
        'source_type',
        'chunk_index',
        'heading',
        'content',
        'content_hash',
        'metadata',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'metadata' => 'array',
        ];
    }
}
