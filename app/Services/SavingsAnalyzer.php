<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SavingsAnalyzer
{
    /**
     * @return array{
     *   monthly: float, yearly: int, count: int,
     *   by_category: Collection<int, array{name: string, monthly: float, share: float}>,
     *   top: Collection<int, Contract>,
     *   unused: Collection<int, Contract>,
     *   increases: Collection<int, array{contract: Contract, change: \App\Models\PriceChange, yearly_delta: int}>,
     *   compare: Collection<int, array{contract: Contract, url: string}>,
     * }
     */
    public function analyze(User $user, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();

        $contracts = $user->contracts()
            ->whereIn('status', ['active', 'cancellation_planned'])
            ->with(['category', 'template', 'priceChanges'])
            ->get();

        $monthly = $contracts->sum(fn (Contract $c) => $c->monthlyCost());

        $byCategory = $contracts
            ->groupBy(fn (Contract $c) => $c->category?->name ?? 'Ohne Kategorie')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'monthly' => $group->sum(fn (Contract $c) => $c->monthlyCost()),
                'share' => $monthly > 0 ? $group->sum(fn (Contract $c) => $c->monthlyCost()) / $monthly : 0,
            ])
            ->sortByDesc('monthly')
            ->values();

        $increases = $contracts->flatMap(function (Contract $c) use ($today) {
            return $c->priceChanges
                ->filter(fn ($p) => $p->difference() > 0 && $p->effective_date->gte($today->subYear()))
                ->map(fn ($p) => [
                    'contract' => $c,
                    'change' => $p,
                    'yearly_delta' => (int) round($c->cost_interval->toMonthly($p->difference()) * 12),
                ]);
        })->sortByDesc('yearly_delta')->values();

        return [
            'monthly' => $monthly,
            'yearly' => (int) round($monthly * 12),
            'count' => $contracts->count(),
            'by_category' => $byCategory,
            'top' => $contracts->sortByDesc(fn (Contract $c) => $c->monthlyCost())->take(5)->values(),
            'unused' => $contracts->filter(fn (Contract $c) => $c->isUnused($today))->values(),
            'increases' => $increases,
            'compare' => $contracts
                ->filter(fn (Contract $c) => filled($c->template?->compare_url))
                ->map(fn (Contract $c) => ['contract' => $c, 'url' => $c->template->compare_url])
                ->values(),
        ];
    }
}
