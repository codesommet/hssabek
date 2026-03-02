<td class="action-item">
    <a href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="isax isax-more"></i>
    </a>
    <ul class="dropdown-menu">
        <li>
            <a href="{{ route('sa.tenants.show', $tenant) }}" class="dropdown-item d-flex align-items-center">
                <i class="isax isax-eye me-2"></i>Voir
            </a>
        </li>
        <li>
            <a href="{{ route('sa.tenants.edit', $tenant) }}" class="dropdown-item d-flex align-items-center">
                <i class="isax isax-edit me-2"></i>Modifier
            </a>
        </li>
        <li>
            @if($tenant->status === 'active')
                <form method="POST" action="{{ route('sa.tenants.suspend', $tenant) }}">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center text-warning">
                        <i class="isax isax-slash me-2"></i>Suspendre
                    </button>
                </form>
            @elseif($tenant->status === 'suspended')
                <form method="POST" action="{{ route('sa.tenants.activate', $tenant) }}">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center text-success">
                        <i class="isax isax-tick-circle me-2"></i>Activer
                    </button>
                </form>
            @endif
        </li>
        <li>
            <form method="POST" action="{{ route('sa.tenants.destroy', $tenant) }}" onsubmit="return confirm('Supprimer ce tenant ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item d-flex align-items-center text-danger">
                    <i class="isax isax-trash me-2"></i>Supprimer
                </button>
            </form>
        </li>
    </ul>
</td>
