<div class="table-responsive table-nowrap">
    <table class="table border mb-0">
        <thead class="table-light">
            <tr>
                <th>Nom</th>
                <th>Slug</th>
                <th>Domaine</th>
                <th>Utilisateurs</th>
                <th>Statut</th>
                <th>Cr&eacute;&eacute; le</th>
                <th class="no-sort"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $tenant)
                <tr>
                    <td>{{ $tenant->name }}</td>
                    <td><code>{{ $tenant->slug }}</code></td>
                    <td>{{ $tenant->domains->first()?->domain ?? '—' }}</td>
                    <td>{{ $tenant->users_count ?? 0 }}</td>
                    <td>
                        @if($tenant->status === 'active')
                            <span class="badge bg-success-transparent">Actif</span>
                        @elseif($tenant->status === 'suspended')
                            <span class="badge bg-warning-transparent">Suspendu</span>
                        @else
                            <span class="badge bg-danger-transparent">{{ ucfirst($tenant->status) }}</span>
                        @endif
                    </td>
                    <td>{{ $tenant->created_at->format('d/m/Y') }}</td>
                    @include('backoffice.tenants.partials._actions', ['tenant' => $tenant])
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Aucun enregistrement trouv&eacute;.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($tenants->hasPages())
    <div class="mt-3">
        {{ $tenants->links() }}
    </div>
@endif
