<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = DashboardService::getStats();

        return view(

            'dashboard.index',

            compact('stats')

        );
    }
}