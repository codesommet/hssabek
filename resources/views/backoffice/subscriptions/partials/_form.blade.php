{{-- Placeholder – UI structure aligned with theme. --}}
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Tenant</label>
        <select class="form-select" name="tenant_id">
            <option value="">-- Sélectionner un tenant --</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Plan</label>
        <select class="form-select" name="plan_id">
            <option value="">-- Sélectionner un plan --</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Date de début</label>
        <input type="date" class="form-control" name="starts_at">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Date de fin</label>
        <input type="date" class="form-control" name="ends_at">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Statut</label>
        <select class="form-select" name="status">
            <option value="">-- Sélectionner --</option>
            <option value="active">Actif</option>
            <option value="expired">Expiré</option>
            <option value="cancelled">Annulé</option>
            <option value="trial">Essai</option>
        </select>
    </div>
</div>
<div class="d-flex gap-2 mt-2">
    <button type="submit" class="btn btn-primary">
        <i class="isax isax-tick-circle me-1"></i>Enregistrer
    </button>
    <a href="#" class="btn btn-outline-white">Annuler</a>
</div>
