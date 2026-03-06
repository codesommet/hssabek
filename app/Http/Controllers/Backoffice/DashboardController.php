<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\System\Announcement;
use App\Services\Reports\ReportService;
use App\Services\Tenancy\TenantContext;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function index()
    {
        $kpis     = $this->reportService->dashboardKpis();
        $currency = TenantContext::get()?->default_currency ?? 'MAD';
        $announcements = Announcement::active()->orderByDesc('published_at')->limit(5)->get();

        return view('backoffice.dashboard', array_merge($kpis, compact('currency', 'announcements')));
    }
}
