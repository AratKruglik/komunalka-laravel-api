<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Billing\Actions\GetExpenseDistribution;
use Modules\Meter\Actions\GetConsumptionHistory;
use Modules\Meter\Actions\GetDashboardStats;
use Modules\Meter\Actions\GetRecentReadings;
use Modules\Meter\Http\Resources\MeterReadingResource;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $addressIds = $user->addresses()->pluck('addresses.id');

        return Inertia::render('Dashboard/Index', [
            'stats' => GetDashboardStats::run($addressIds),
            'consumptionHistory' => GetConsumptionHistory::run($addressIds),
            'expenseDistribution' => GetExpenseDistribution::run($addressIds),
            'recentReadings' => MeterReadingResource::collection(GetRecentReadings::run($addressIds)),
            'addresses' => AddressResource::collection(
                $user->addresses()->with(['region', 'addressType'])->get(),
            ),
        ]);
    }
}
