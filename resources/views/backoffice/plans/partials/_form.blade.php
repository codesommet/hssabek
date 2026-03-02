{{-- Placeholder – UI structure aligned with theme. --}}
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Nom</label>
        <input type="text" class="form-control" name="name" placeholder="Nom du plan">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Prix (MAD)</label>
        <input type="number" class="form-control" name="price" placeholder="0.00" step="0.01">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Durée (jours)</label>
        <input type="number" class="form-control" name="duration_days" placeholder="30">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Nombre d'utilisateurs</label>
        <input type="number" class="form-control" name="max_users" placeholder="5">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Statut</label>
        <select class="form-select" name="status">
            <option value="">-- Sélectionner --</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
        </select>
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Description</label>
        <textarea class="form-control" name="description" rows="3" placeholder="Description du plan..."></textarea>
    </div>
</div>
<div class="d-flex gap-2 mt-2">
    <button type="submit" class="btn btn-primary">
        <i class="isax isax-tick-circle me-1"></i>Enregistrer
    </button>
    <a href="#" class="btn btn-outline-white">Annuler</a>
</div>
