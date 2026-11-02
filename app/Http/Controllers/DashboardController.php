<?php

namespace App\Http\Controllers;

use App\Services\SavingsAnalyzer;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SavingsAnalyzer $savings): View
    {
        $user = $request->user();
        $today = CarbonImmutable::today();

        $upcoming = $user->contracts()
            ->watching()
            ->whereNotNull('send_by')
            ->whereDate('send_by', '<=', $today->addDays(90))
            ->with('category')
            ->orderBy('send_by')
            ->get();

        $next = $upcoming->first();

        $awaitingConfirmation = $user->contracts()
            ->where('status', 'cancelled_unconfirmed')
            ->orderBy('cancellation_sent_on')
            ->get();

        $expiring = $user->expiryItems()
            ->whereDate('expires_at', '<=', $today->addDays(180))
            ->orderBy('expires_at')
            ->get();

        return view('dashboard', [
            'next' => $next,
            'upcoming' => $upcoming->slice(1)->values(),
            'awaitingConfirmation' => $awaitingConfirmation,
            'expiring' => $expiring,
            'stats' => $savings->analyze($user, $today),
            'contractCount' => $user->contracts()->count(),
            'today' => $today,
        ]);
    }
}
