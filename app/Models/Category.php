<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['user_id', 'slug', 'name', 'icon', 'tracks_usage', 'sort'];

    protected function casts(): array
    {
        return ['tracks_usage' => 'boolean'];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** System-Kategorien plus eigene Kategorien des Nutzers. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->orderBy('sort')->orderBy('name');
    }
}
