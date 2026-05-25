<div id="page-content" class="px-4 py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-main mb-0">System Configuration</h3>
            <p class="text-muted small mb-0">Manage global application settings and environment variables</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-soft-primary rounded-pill px-4 fw-bold shadow-sm" onclick="location.reload()">
                <i class="fa fa-sync me-2"></i>Refresh Cache
            </button>
        </div>
    </div>

    <div class="zt-card">
        <div class="zt-card__header border-0 bg-transparent py-4">
            <h5 class="zt-card__title fw-bold text-main mb-0">
                <i class="fa fa-cog text-primary me-2"></i>Global Settings
            </h5>
        </div>
        <div class="zt-card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="ps-4 py-3 text-uppercase small fw-bold text-muted">Setting Key</th>
                            <th class="py-3 text-uppercase small fw-bold text-muted">Value</th>
                            <th class="py-3 text-uppercase small fw-bold text-muted">Description</th>
                            <th class="text-end pe-4 py-3 text-uppercase small fw-bold text-muted">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->settings as $s): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                                        <i class="fa fa-key text-primary small"></i>
                                    </div>
                                    <code class="text-primary fw-bold"><?= htmlspecialchars($s['txt_key'], ENT_QUOTES, 'UTF-8') ?></code>
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium text-dark"><?= htmlspecialchars($s['txt_value'] ?? '', ENT_QUOTES) ?></span>
                            </td>
                            <td>
                                <span class="text-muted small"><?= htmlspecialchars($s['txt_description'] ?? 'No description provided', ENT_QUOTES) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-soft-primary btn-sm rounded-pill px-3 fw-bold" ng-click="editSetting(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
