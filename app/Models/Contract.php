<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\CostInterval;
use App\Enums\NoticeAnchor;
use App\Enums\NoticeUnit;
use App\Services\DeadlineCalculator;
use App\Services\ReminderPlanner;
use Carbon\CarbonImmutable;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id', 'template_id', 'name', 'provider', 'customer_number',
        'cost_amount', 'cost_interval', 'start_date', 'min_term_months', 'renewal_months',
        'notice_anchor', 'fixed_end_dates', 'notice_value', 'notice_unit', 'send_buffer_days',
        'reminder_offsets', 'cancellation_address', 'cancellation_email', 'status',
        'cancellation_sent_on', 'cancellation_effective_on', 'last_used_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'cost_amount' => 'integer',
            'cost_interval' => CostInterval::class,
            'start_date' => 'immutable_date',
            'min_term_months' => 'integer',
            'renewal_months' => 'integer',
            'notice_anchor' => NoticeAnchor::class,
            'fixed_end_dates' => 'array',
            'notice_value' => 'integer',
            'notice_unit' => NoticeUnit::class,
            'send_buffer_days' => 'integer',
            'reminder_offsets' => 'array',
            'status' => ContractStatus::class,
            'cancellation_sent_on' => 'immutable_date',
            'cancellation_effective_on' => 'immutable_date',
            'last_used_at' => 'immutable_datetime',
            'next_term_end' => 'immutable_date',
            'receipt_deadline' => 'immutable_date',
            'send_by' => 'immutable_date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Contract $contract) {
            $contract->applyDeadlines();
        });

        static::saved(function (Contract $contract) {
            app(ReminderPlanner::class)->planForContract($contract);
        });

        static::deleting(function (Contract $contract) {
            $contract->reminders()->delete();
            $contract->priceChanges->each(fn (PriceChange $p) => $p->reminders()->delete());
            $contract->documents->each->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    public function priceChanges(): HasMany
    {
        return $this->hasMany(PriceChange::class)->orderBy('effective_date');
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'remindable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ContractStatus::open());
    }

    public function scopeWatching(Builder $query): Builder
    {
        return $query->whereIn('status', [ContractStatus::Active, ContractStatus::CancellationPlanned]);
    }

    /** Fristen berechnen und in die gespeicherten Spalten schreiben. */
    public function applyDeadlines(?CarbonImmutable $today = null): void
    {
        $user = $this->user ?? User::find($this->user_id);
        $result = app(DeadlineCalculator::class)->forContract($this, $today, $user);

        $this->next_term_end = $result->termEnd;
        $this->receipt_deadline = $result->receiptDeadline;
        $this->send_by = $result->sendBy;
    }

    public function monthlyCost(): float
    {
        return $this->cost_interval->toMonthly($this->cost_amount);
    }

    public function yearlyCost(): int
    {
        return (int) round($this->monthlyCost() * 12);
    }

    public function daysUntilSendBy(?CarbonImmutable $today = null): ?int
    {
        if (! $this->send_by) {
            return null;
        }

        $today ??= CarbonImmutable::today();

        return (int) $today->startOfDay()->diffInDays($this->send_by->startOfDay(), false);
    }

    /** critical | soon | ok | none */
    public function urgency(?CarbonImmutable $today = null): string
    {
        if (! $this->status->watchesDeadline()) {
            return 'none';
        }

        $days = $this->daysUntilSendBy($today);

        return match (true) {
            $days === null => 'none',
            $days < 14 => 'critical',
            $days < 45 => 'soon',
            default => 'ok',
        };
    }

    public function noticeLabel(): string
    {
        if ($this->notice_value === 0) {
            return 'ohne Frist';
        }

        return $this->notice_value.' '.$this->notice_unit->label($this->notice_value);
    }

    public function isUnused(?CarbonImmutable $today = null): bool
    {
        if (! $this->category?->tracks_usage || $this->status !== ContractStatus::Active) {
            return false;
        }

        $today ??= CarbonImmutable::today();
        $since = $this->last_used_at ?? CarbonImmutable::parse($this->created_at);

        return $since->lt($today->subDays((int) config('fristwatcher.unused_after_days')));
    }
}
