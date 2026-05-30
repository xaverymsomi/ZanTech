<div id="page-content" class="px-4 py-4">
    <!-- 1. Header / Identity -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold text-main mb-1"><i class="fa fa-terminal text-primary me-2"></i> <?= htmlspecialchars($this->title) ?></h3>
            <p class="text-muted small mb-0">
                Welcome, <strong><?= htmlspecialchars($this->user['txt_username'] ?? 'Developer') ?></strong> 
                | Environment: <span class="badge bg-secondary"><?= htmlspecialchars($this->environment) ?></span>
                | DB Engine: <span class="badge bg-info"><?= htmlspecialchars($this->dbIntel['engine']) ?></span>
                | <i class="fa fa-clock"></i> <?= date('Y-m-d H:i:s') ?>
            </p>
        </div>
        <button onclick="window.location.reload()" class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center gap-2">
            <i class="fa fa-sync"></i> Refresh
        </button>
    </div>

    <!-- 8. Developer Warning Queue (Prioritized) -->
    <?php if (!empty($this->warnings)): ?>
        <div class="alert alert-danger shadow-sm mb-4">
            <h5 class="fw-bold mb-3"><i class="fa fa-exclamation-triangle me-2"></i> Developer Warning Queue</h5>
            <ul class="mb-0">
                <?php foreach ($this->warnings as $warning): ?>
                    <li><?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- 2. Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="zt-card p-3 border-start border-primary border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Total Users</div>
                <h3 class="fw-bold mb-0"><?= $this->dbIntel['core_tables']['mx_user']['count'] >= 0 ? $this->dbIntel['core_tables']['mx_user']['count'] : 'N/A' ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="zt-card p-3 border-start border-warning border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Modules Detected</div>
                <h3 class="fw-bold mb-0"><?= count($this->moduleInventory) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="zt-card p-3 border-start border-danger border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Failed Logins Today</div>
                <h3 class="fw-bold mb-0"><?= $this->securityOverview['failed_logins_today'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="zt-card p-3 border-start border-success border-4 h-100">
                <div class="text-muted small fw-bold text-uppercase">Active Permissions</div>
                <h3 class="fw-bold mb-0"><?= $this->dbIntel['core_tables']['mx_permission']['count'] >= 0 ? $this->dbIntel['core_tables']['mx_permission']['count'] : 'N/A' ?></h3>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- 3. Runtime Health -->
        <div class="col-lg-6">
            <div class="zt-card p-4 h-100">
                <h5 class="fw-bold text-main mb-3"><i class="fa fa-heartbeat text-danger me-2"></i> Runtime Health</h5>
                <ul class="list-group list-group-flush">
                    <?php foreach ($this->runtimeHealth as $key => $status): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                            <span class="text-capitalize"><?= str_replace('_', ' ', $key) ?></span>
                            <?php if ($status === 'Healthy'): ?>
                                <span class="badge bg-success rounded-pill"><i class="fa fa-check"></i> Healthy</span>
                            <?php elseif ($status === 'Warning'): ?>
                                <span class="badge bg-warning text-dark rounded-pill"><i class="fa fa-exclamation"></i> Warning</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill"><i class="fa fa-times"></i> <?= htmlspecialchars($status) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- 4. Security Overview -->
        <div class="col-lg-6">
            <div class="zt-card p-4 h-100">
                <h5 class="fw-bold text-main mb-3"><i class="fa fa-shield-alt text-success me-2"></i> Security Overview</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span>Successful Logins Today</span>
                        <span class="badge bg-primary rounded-pill"><?= $this->securityOverview['successful_logins_today'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span>Failed Logins Today</span>
                        <span class="badge bg-danger rounded-pill"><?= $this->securityOverview['failed_logins_today'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span>Locked/Inactive Credentials</span>
                        <span class="badge bg-warning text-dark rounded-pill"><?= $this->securityOverview['locked_users'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span>Audit Trail Records</span>
                        <span class="badge bg-secondary rounded-pill"><?= $this->dbIntel['core_tables']['mx_audit_trail']['count'] >= 0 ? $this->dbIntel['core_tables']['mx_audit_trail']['count'] : 'Missing' ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 6. Module / Route Inventory -->
    <div class="zt-card p-4 mb-4">
        <h5 class="fw-bold text-main mb-3"><i class="fa fa-cubes text-info me-2"></i> Module Inventory</h5>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Module Name</th>
                        <th class="text-center">Controller</th>
                        <th class="text-center">Model</th>
                        <th class="text-center">View</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->moduleInventory as $mod): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($mod['name']) ?></td>
                            <td class="text-center"><?= $mod['controller'] ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>' ?></td>
                            <td class="text-center"><?= $mod['model'] ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-minus text-muted"></i>' ?></td>
                            <td class="text-center"><?= $mod['view'] ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-minus text-muted"></i>' ?></td>
                            <td>
                                <?php if ($mod['status'] === 'Healthy'): ?>
                                    <span class="badge bg-success">Healthy</span>
                                <?php elseif ($mod['status'] === 'Partial'): ?>
                                    <span class="badge bg-warning text-dark">Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Broken</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <!-- 7. Database Intelligence -->
        <div class="col-lg-6">
            <div class="zt-card p-4 h-100">
                <h5 class="fw-bold text-main mb-3"><i class="fa fa-database text-secondary me-2"></i> Database Intelligence</h5>
                <p class="small text-muted mb-2">Core tables essential to the framework's operation.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <tbody>
                            <?php foreach ($this->dbIntel['core_tables'] as $tableName => $info): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($tableName) ?></code></td>
                                    <td class="text-end">
                                        <?php if ($info['exists']): ?>
                                            <span class="badge bg-light text-dark border"><?= $info['count'] ?> rows</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Missing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. Activity Feed -->
        <div class="col-lg-6">
            <div class="zt-card p-4 h-100">
                <h5 class="fw-bold text-main mb-3"><i class="fa fa-list-ul text-primary me-2"></i> Recent Activity</h5>
                <?php if (empty($this->activityFeed)): ?>
                    <div class="text-muted text-center py-3">No recent activity found or audit trail disabled.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($this->activityFeed as $activity): ?>
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <strong class="mb-1 text-truncate" style="max-width: 70%;"><?= htmlspecialchars($activity['txt_action']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars(substr($activity['dat_created_at'], 11, 8)) ?></small>
                                </div>
                                <p class="mb-0 small text-muted">
                                    <i class="fa fa-user me-1"></i> <?= htmlspecialchars($activity['txt_username'] ?? 'System') ?>
                                    <?php if (!empty($activity['txt_module'])): ?>
                                        | Module: <?= htmlspecialchars($activity['txt_module']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
