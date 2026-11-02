<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Beispieldaten für Screenshots und lokale Entwicklung: `php artisan db:seed --class=DemoSeeder` */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);

        $user = User::updateOrCreate(['email' => 'demo@example.ch'], [
            'name' => 'Stefan Muster',
            'password' => 'demo-passwort-123',
            'street' => 'Musterstrasse 12',
            'zip' => '9000',
            'city' => 'St. Gallen',
            'canton' => 'SG',
            'email_verified_at' => now(),
        ]);

        $cat = fn (string $slug) => Category::whereNull('user_id')->where('slug', $slug)->value('id');
        $tpl = fn (string $slug) => ContractTemplate::where('slug', $slug)->value('id');

        $contracts = [
            ['name' => 'Grundversicherung', 'provider' => 'Helsana', 'category_id' => $cat('krankenkasse'), 'template_id' => $tpl('kvg-grundversicherung'),
                'cost_amount' => 41260, 'cost_interval' => 'monthly', 'start_date' => '2021-01-01', 'notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['12-31'],
                'notice_value' => 1, 'notice_unit' => 'months', 'customer_number' => '756.1234.5678', 'cancellation_address' => "Helsana Versicherungen AG\nPostfach\n8081 Zürich"],
            ['name' => 'Fitnessabo', 'provider' => 'Activ Fitness', 'category_id' => $cat('fitness'), 'template_id' => $tpl('fitness-abo'),
                'cost_amount' => 89000, 'cost_interval' => 'yearly', 'start_date' => '2025-01-15', 'min_term_months' => 12, 'renewal_months' => 12,
                'notice_value' => 2, 'notice_unit' => 'months', 'last_used_at' => now()->subDays(80)],
            ['name' => 'Handy-Abo', 'provider' => 'Swisscom', 'category_id' => $cat('telekom'), 'template_id' => $tpl('telekom-abo'),
                'cost_amount' => 6500, 'cost_interval' => 'monthly', 'start_date' => '2025-03-01', 'min_term_months' => 24, 'renewal_months' => 1,
                'notice_value' => 2, 'notice_unit' => 'months'],
            ['name' => 'Netflix', 'provider' => 'Netflix', 'category_id' => $cat('streaming'), 'template_id' => $tpl('streaming'),
                'cost_amount' => 2190, 'cost_interval' => 'monthly', 'start_date' => '2024-11-18', 'min_term_months' => 1, 'renewal_months' => 1,
                'notice_value' => 0, 'notice_unit' => 'days', 'send_buffer_days' => 1, 'last_used_at' => now()->subDays(4)],
            ['name' => 'Hausrat und Privathaftpflicht', 'provider' => 'Mobiliar', 'category_id' => $cat('versicherung'), 'template_id' => $tpl('vvg-sachversicherung'),
                'cost_amount' => 38400, 'cost_interval' => 'yearly', 'start_date' => '2023-04-01', 'min_term_months' => 36, 'renewal_months' => 12,
                'notice_value' => 3, 'notice_unit' => 'months'],
            ['name' => 'Zusatzversicherung', 'provider' => 'Helsana', 'category_id' => $cat('krankenkasse'), 'template_id' => $tpl('kvg-zusatz'),
                'cost_amount' => 4580, 'cost_interval' => 'monthly', 'start_date' => '2021-01-01', 'notice_anchor' => 'fixed_dates', 'fixed_end_dates' => ['12-31'],
                'notice_value' => 3, 'notice_unit' => 'months'],
        ];

        $user->contracts()->delete();
        foreach ($contracts as $data) {
            $c = $user->contracts()->make($data);
            $c->forceFill(['last_used_at' => $data['last_used_at'] ?? null])->save();
        }

        $kvg = $user->contracts()->where('name', 'Grundversicherung')->first();
        $kvg->priceChanges()->create(['old_amount' => 38940, 'new_amount' => 41260, 'effective_date' => '2026-01-01', 'announced_at' => '2025-10-01', 'applied_at' => now()]);

        $user->expiryItems()->delete();
        $user->expiryItems()->create(['type' => 'passport', 'name' => 'Pass Stefan', 'expires_at' => now()->addDays(150)->toDateString(), 'reference_last4' => '4821']);
        $user->expiryItems()->create(['type' => 'warranty', 'name' => 'Garantie Laptop', 'expires_at' => now()->addDays(400)->toDateString()]);
    }
}
