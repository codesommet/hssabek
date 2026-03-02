<?php

namespace Tests\Feature\Tenancy;

use App\Models\CRM\Customer;
use App\Models\Tenancy\Tenant;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::forget();
        parent::tearDown();
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name'             => 'Tenant ' . $slug,
            'slug'             => $slug,
            'status'           => 'active',
            'timezone'         => 'UTC',
            'default_currency' => 'USD',
            'has_free_trial'   => false,
        ]);
    }

    /**
     * TenantScope must prevent tenant A from reading tenant B's customer.
     */
    public function test_tenant_a_cannot_read_tenant_b_customer(): void
    {
        $tenantA = $this->createTenant('tenant-a');
        $tenantB = $this->createTenant('tenant-b');

        // Create a customer for tenant B
        TenantContext::set($tenantB);
        $customerB = Customer::create([
            'name'   => 'Customer B',
            'email'  => 'b@example.com',
            'type'   => 'individual',
            'status' => 'active',
        ]);
        TenantContext::forget();

        // Switch to tenant A context
        TenantContext::set($tenantA);

        // TenantScope should prevent finding tenant B's customer
        $found = Customer::find($customerB->id);

        $this->assertNull($found, 'Tenant A must not be able to read Tenant B\'s customer.');
    }

    /**
     * Tenant A's customers must only appear in Tenant A's queries.
     */
    public function test_query_returns_only_current_tenant_customers(): void
    {
        $tenantA = $this->createTenant('tenant-a2');
        $tenantB = $this->createTenant('tenant-b2');

        TenantContext::set($tenantA);
        Customer::create([
            'name'   => 'Customer A1',
            'email'  => 'a1@example.com',
            'type'   => 'individual',
            'status' => 'active',
        ]);
        TenantContext::forget();

        TenantContext::set($tenantB);
        Customer::create([
            'name'   => 'Customer B1',
            'email'  => 'b1@example.com',
            'type'   => 'individual',
            'status' => 'active',
        ]);

        $customers = Customer::all();

        $this->assertCount(1, $customers);
        $this->assertEquals('Customer B1', $customers->first()->name);
    }
}
