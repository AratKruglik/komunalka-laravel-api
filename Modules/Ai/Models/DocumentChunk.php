<?php

namespace Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $source_path
 * @property string|null $heading
 * @property string $content
 * @property string $content_hash
 * @property array<float> $embedding
 * @property array<string, mixed>|null $metadata
 */
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
