<?php

namespace App\Policies;

use App\Models\Purchases\GoodsReceipt;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.goods_receipts.view');
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('purchases.goods_receipts.view')
            && $goodsReceipt->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.goods_receipts.create');
    }

    public function update(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('purchases.goods_receipts.edit')
            && $goodsReceipt->tenant_id === TenantContext::id();
    }

    public function delete(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('purchases.goods_receipts.delete')
            && $goodsReceipt->tenant_id === TenantContext::id();
    }
}
