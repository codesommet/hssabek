<?php

namespace App\Policies;

use App\Models\Reports\CustomReport;
use App\Models\User;

class CustomReportPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reports.custom.view');
    }

    public function view(User $user, CustomReport $report): bool
    {
        return $user->can('reports.custom.view')
            && $this->belongsToTenant($report);
    }

    public function create(User $user): bool
    {
        return $user->can('reports.custom.create');
    }

    public function update(User $user, CustomReport $report): bool
    {
        return $user->can('reports.custom.edit')
            && $this->belongsToTenant($report);
    }

    public function delete(User $user, CustomReport $report): bool
    {
        return $user->can('reports.custom.delete')
            && $this->belongsToTenant($report);
    }

    public function export(User $user, CustomReport $report): bool
    {
        return $user->can('reports.custom.export')
            && $this->belongsToTenant($report);
    }
}
