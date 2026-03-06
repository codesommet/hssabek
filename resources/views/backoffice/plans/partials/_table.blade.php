<div class="table-responsive no-pagination">
    <table class="table table-nowrap datatable">
        <thead class="thead-light">
            <tr>
                <th>Plan</th>
                <th>Code</th>
                <th>Intervalle</th>
                <th>Prix</th>
                <th>Utilisateurs</th>
                <th>Factures/mois</th>
                <th>Exports/mois</th>
                <th>Abonnés</th>
                <th>Essai</th>
                <th>Statut</th>
                <th class="no-sort"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td>
                        <a href="javascript:void(0);" class="text-dark fw-medium" data-bs-toggle="modal" data-bs-target="#view_plan_{{ $plan->id }}">
                            {{ $plan->name }}
                            @if($plan->is_popular)
                                <span class="badge bg-success ms-1">Populaire</span>
                            @endif
                        </a>
                    </td>
                    <td><code>{{ $plan->code }}</code></td>
                    <td>
                        @switch($plan->interval)
                            @case('month') Mensuel @break
                            @case('year') Annuel @break
                            @case('lifetime') À vie @break
                            @default {{ $plan->interval }}
                        @endswitch
                    </td>
                    <td>{{ number_format($plan->price, 2, ',', ' ') }} {{ $plan->currency }}</td>
                    <td>
                        @if($plan->max_users === null)
                            <span class="badge badge-soft-info">Illimité</span>
                        @else
                            {{ $plan->max_users }}
                        @endif
                    </td>
                    <td>
                        @if($plan->max_invoices_per_month === null)
                            <span class="badge badge-soft-info">Illimité</span>
                        @else
                            {{ $plan->max_invoices_per_month }}
                        @endif
                    </td>
                    <td>
                        @if($plan->max_exports_per_month === null)
                            <span class="badge badge-soft-info">Illimité</span>
                        @else
                            {{ $plan->max_exports_per_month }}
                        @endif
                    </td>
                    <td>{{ $plan->subscriptions_count ?? 0 }}</td>
                    <td>{{ $plan->trial_days ? $plan->trial_days . ' jours' : '—' }}</td>
                    <td>
                        @if($plan->is_active)
                            <span class="badge badge-soft-success d-inline-flex align-items-center">Actif
                                <i class="isax isax-tick-circle ms-1"></i>
                            </span>
                        @else
                            <span class="badge badge-soft-danger d-inline-flex align-items-center">Inactif
                                <i class="isax isax-close-circle ms-1"></i>
                            </span>
                        @endif
                    </td>
                    @include('backoffice.plans.partials._actions', ['plan' => $plan])
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">Aucun plan trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
