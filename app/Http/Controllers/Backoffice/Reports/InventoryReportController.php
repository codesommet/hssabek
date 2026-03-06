<?php

namespace App\Http\Controllers\Backoffice\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\ExportReportJob;
use App\Services\Reports\ReportService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function index(Request $request)
    {
        $data = $this->reportService->inventorySummary();

        return view('backoffice.reports.inventory', $data);
    }

    public function export(Request $request)
    {
        dispatch(new ExportReportJob(
            tenantId: TenantContext::id(),
            type: 'inventory',
            from: now()->startOfYear()->toDateString(),
            to: now()->toDateString(),
            userId: auth()->id(),
        ));

        return redirect()->back()
            ->with('info', 'L\'export est en cours. Vous serez notifié lorsqu\'il sera prêt.');
    }
}
