<?php

namespace App\Models;

use App\Enums\ReminderKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reminder extends Model
{
    protected $fillable = ['user_id', 'kind', 'target_date', 'due_on', 'offset_days', 'sent_at'];

    protected function casts(): array
    {
        return [
            'kind' => ReminderKind::class,
            'target_date' => 'immutable_date',
            'due_on' => 'immutable_date',
            'offset_days' => 'integer',
            'sent_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }
}
