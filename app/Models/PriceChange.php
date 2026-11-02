<?php

namespace App\Models;

use App\Services\ReminderPlanner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PriceChange extends Model
{
    protected $fillable = [
        'old_amount', 'new_amount', 'effective_date', 'announced_at', 'special_termination_deadline', 'note',
    ];

    protected function casts(): array
    {
        return [
            'old_amount' => 'integer',
            'new_amount' => 'integer',
            'effective_date' => 'immutable_date',
            'announced_at' => 'immutable_date',
            'special_termination_deadline' => 'immutable_date',
            'applied_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (PriceChange $p) => app(ReminderPlanner::class)->planForPriceChange($p));
        static::deleting(fn (PriceChange $p) => $p->reminders()->delete());
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'remindable');
    }

    /** Differenz in Rappen, auf das Intervall des Vertrags bezogen. */
    public function difference(): int
    {
        return $this->new_amount - $this->old_amount;
    }

    public function percent(): ?float
    {
        return $this->old_amount > 0 ? round($this->difference() / $this->old_amount * 100, 1) : null;
    }
}
