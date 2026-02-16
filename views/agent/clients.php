<div class="container-fluid pt-4">
    <div class="card page-header mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="bi bi-people text-primary me-2"></i>Clients</h1>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="bi bi-plus-circle me-1"></i> Add Client
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Notes</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($clients ?? []) as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($c['property_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['phone'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($c['email'] ?? '')) ?></td>
                                <td><?= nl2br(htmlspecialchars((string)($c['notes'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string)($c['created_at'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No clients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/agent/clients/store">
                <div class="modal-header">
                    <h5 class="modal-title">Add Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <select class="form-select" name="property_id" required>
                                <option value="">Select property</option>
                                <?php foreach (($properties ?? []) as $p): ?>
                                    <option value="<?= (int)($p['id'] ?? 0) ?>"><?= htmlspecialchars((string)($p['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
