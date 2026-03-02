{{-- Placeholder – UI structure aligned with theme. --}}
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Nom</label>
        <input type="text" class="form-control" name="name" placeholder="Nom du tenant">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Slug</label>
        <input type="text" class="form-control" name="slug" placeholder="slug-du-tenant">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Domaine</label>
        <input type="text" class="form-control" name="domain" placeholder="exemple.com">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" placeholder="contact@exemple.com">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Statut</label>
        <select class="form-select" name="status">
            <option value="">-- Sélectionner --</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
            <option value="suspended">Suspendu</option>
        </select>
    </div>
</div>
<div class="d-flex gap-2 mt-2">
    <button type="submit" class="btn btn-primary">
        <i class="isax isax-tick-circle me-1"></i>Enregistrer
    </button>
    <a href="#" class="btn btn-outline-white">Annuler</a>
</div>
