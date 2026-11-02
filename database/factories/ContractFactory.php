<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contract> */
class ContractFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Fitnessabo',
            'provider' => fake()->company(),
            'cost_amount' => 7900,
            'cost_interval' => 'monthly',
            'start_date' => now()->subMonths(3)->toDateString(),
            'min_term_months' => 12,
            'renewal_months' => 12,
            'notice_anchor' => 'term_end',
            'notice_value' => 2,
            'notice_unit' => 'months',
            'status' => 'active',
        ];
    }

    public function kvg(): static
    {
        return $this->state([
            'name' => 'Grundversicherung',
            'provider' => 'Musterkasse',
            'cost_amount' => 42000,
            'notice_anchor' => 'fixed_dates',
            'fixed_end_dates' => ['12-31'],
            'notice_value' => 1,
            'notice_unit' => 'months',
            'min_term_months' => null,
            'renewal_months' => null,
        ]);
    }
}
