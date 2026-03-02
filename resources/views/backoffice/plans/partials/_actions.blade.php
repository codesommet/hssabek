<td class="action-item">
    <a href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="isax isax-more"></i>
    </a>
    <ul class="dropdown-menu">
        <li>
            <a href="{{ route('sa.plans.show', $plan) }}" class="dropdown-item d-flex align-items-center">
                <i class="isax isax-eye me-2"></i>Voir
            </a>
        </li>
        <li>
            <a href="{{ route('sa.plans.edit', $plan) }}" class="dropdown-item d-flex align-items-center">
                <i class="isax isax-edit me-2"></i>Modifier
            </a>
        </li>
        <li>
            <form method="POST" action="{{ route('sa.plans.destroy', $plan) }}" onsubmit="return confirm('Supprimer ce plan ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item d-flex align-items-center text-danger">
                    <i class="isax isax-trash me-2"></i>Supprimer
                </button>
            </form>
        </li>
    </ul>
</td>
