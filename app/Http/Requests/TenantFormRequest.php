<?php

namespace App\Http\Requests;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rule;

/**
 * Base FormRequest for tenant-scoped validation.
 * All backoffice FormRequests that reference tenant-owned tables
 * should extend this class and use the helper methods below.
 */
abstract class TenantFormRequest extends FormRequest
{
    /**
     * Create a tenant-scoped "exists" rule.
     * Equivalent to: Rule::exists($table, $column)->where('tenant_id', TenantContext::id())
     */
    protected function tenantExists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('tenant_id', TenantContext::id());
    }

    /**
     * Get the current tenant ID.
     */
    protected function tenantId(): string
    {
        return TenantContext::id();
    }
}
