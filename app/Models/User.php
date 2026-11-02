<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'street', 'zip', 'city', 'canton', 'settings',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function expiryItems(): HasMany
    {
        return $this->hasMany(ExpiryItem::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    /** @return list<int> */
    public function reminderOffsets(): array
    {
        $offsets = $this->setting('reminder_offsets', config('fristwatcher.default_offsets'));

        return collect($offsets)->map(fn ($d) => (int) $d)->filter(fn ($d) => $d >= 0)->unique()->sortDesc()->values()->all();
    }

    public function sendBufferDays(): int
    {
        return (int) $this->setting('send_buffer_days', config('fristwatcher.default_send_buffer_days'));
    }

    public function wantsMail(): bool
    {
        return (bool) $this->setting('notify_mail', true);
    }

    public function wantsPush(): bool
    {
        return (bool) $this->setting('notify_push', true);
    }

    public function hasPostalAddress(): bool
    {
        return filled($this->street) && filled($this->zip) && filled($this->city);
    }
}
