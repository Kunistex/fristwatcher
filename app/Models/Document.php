<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = ['user_id', 'kind', 'path', 'original_name', 'mime', 'size'];

    protected static function booted(): void
    {
        static::deleting(fn (Document $d) => Storage::disk('local')->delete($d->path));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'policy' => 'Vertrag / Police',
            'letter' => 'Kündigungsschreiben',
            'confirmation' => 'Bestätigung',
            default => 'Dokument',
        };
    }
}
