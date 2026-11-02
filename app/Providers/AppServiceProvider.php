<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\ExpiryItem;
use App\Models\PriceChange;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\HolidayCalendar::class);
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        CarbonImmutable::setLocale('de');

        Relation::enforceMorphMap([
            'contract' => Contract::class,
            'expiry_item' => ExpiryItem::class,
            'price_change' => PriceChange::class,
            'user' => \App\Models\User::class,
        ]);

        // CHF-Betrag aus Rappen, Schweizer Format: 1'234.50
        Blade::directive('chf', fn ($expr) => "<?php echo \\App\\Support\\Money::format($expr); ?>");
    }
}
