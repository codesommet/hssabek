<div class="table-responsive table-nowrap">
    <table class="table border mb-0">
        <thead class="table-light">
            <tr>
                <th>Nom</th>
                <th>Code</th>
                <th>Prix</th>
                <th>Intervalle</th>
                <th>Essai</th>
                <th>Statut</th>
                <th>Cr&eacute;&eacute; le</th>
                <th class="no-sort"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td>{{ $plan->name }}</td>
                    <td><code>{{ $plan->code }}</code></td>
                    <td>{{ number_format($plan->price, 2, ',', ' ') }} {{ $plan->currency ?? 'MAD' }}</td>
                    <td>
                        @switch($plan->interval)
                            @case('month') Mensuel @break
                            @case('year') Annuel @break
                            @case('lifetime') &Agrave; vie @break
                            @default {{ $plan->interval }}
                        @endswitch
                    </td>
                    <td>{{ $plan->trial_days ? $plan->trial_days . ' jours' : '—' }}</td>
                    <td>
                        @if($plan->is_active)
                            <span class="badge bg-success-transparent">Actif</span>
                        @else
                            <span class="badge bg-danger-transparent">Inactif</span>
                        @endif
                    </td>
                    <td>{{ $plan->created_at->format('d/m/Y') }}</td>
                    @include('backoffice.plans.partials._actions', ['plan' => $plan])
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Aucun enregistrement trouv&eacute;.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
