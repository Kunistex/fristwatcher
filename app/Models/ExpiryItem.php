<?php

namespace App\Models;

use App\Enums\ExpiryType;
use App\Services\ReminderPlanner;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpiryItem extends Model
{
    protected $fillable = ['type', 'name', 'expires_at', 'reference_last4', 'notes'];

    protected function casts(): array
    {
        return [
            'type' => ExpiryType::class,
            'expires_at' => 'immutable_date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (ExpiryItem $item) => app(ReminderPlanner::class)->planForExpiry($item));
        static::deleting(fn (ExpiryItem $item) => $item->reminders()->delete());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'remindable');
    }

    public function daysLeft(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();

        return (int) $today->diffInDays($this->expires_at, false);
    }

    public function urgency(?CarbonImmutable $today = null): string
    {
        $days = $this->daysLeft($today);
        $first = $this->type->offsets()[0] ?? 90;

        return match (true) {
            $days < 0 => 'expired',
            $days < 30 => 'critical',
            $days <= $first => 'soon',
            default => 'ok',
        };
    }
}
