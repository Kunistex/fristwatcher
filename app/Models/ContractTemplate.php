<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTemplate extends Model
{
    protected $fillable = ['slug', 'name', 'category_id', 'defaults', 'help_text', 'compare_url', 'sort'];

    protected function casts(): array
    {
        return ['defaults' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
