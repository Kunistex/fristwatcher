<?php

namespace App\Http\Controllers;

use App\Services\SavingsAnalyzer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavingsController extends Controller
{
    public function __invoke(Request $request, SavingsAnalyzer $analyzer): View
    {
        return view('savings', ['s' => $analyzer->analyze($request->user())]);
    }
}
