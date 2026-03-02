<div class="table-responsive table-nowrap">
    <table class="table border mb-0">
        <thead class="table-light">
            <tr>
                <th>Tenant</th>
                <th>Plan</th>
                <th>Date de d&eacute;but</th>
                <th>Date de fin</th>
                <th>Statut</th>
                <th>Cr&eacute;&eacute; le</th>
                <th class="no-sort"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($subscriptions as $subscription)
                <tr>
                    <td>{{ $subscription->tenant?->name ?? '—' }}</td>
                    <td>{{ $subscription->plan?->name ?? '—' }}</td>
                    <td>{{ $subscription->starts_at?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $subscription->ends_at?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        @switch($subscription->status)
                            @case('active')
                                <span class="badge bg-success-transparent">Actif</span>
                                @break
                            @case('trialing')
                                <span class="badge bg-info-transparent">Essai</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger-transparent">Annul&eacute;</span>
                                @break
                            @case('expired')
                                <span class="badge bg-secondary-transparent">Expir&eacute;</span>
                                @break
                            @default
                                <span class="badge bg-secondary-transparent">{{ ucfirst($subscription->status) }}</span>
                        @endswitch
                    </td>
                    <td>{{ $subscription->created_at->format('d/m/Y') }}</td>
                    @include('backoffice.subscriptions.partials._actions', ['subscription' => $subscription])
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Aucun enregistrement trouv&eacute;.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($subscriptions->hasPages())
    <div class="mt-3">
        {{ $subscriptions->links() }}
    </div>
@endif
