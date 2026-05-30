<div id="page-content" class="px-4 py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-main mb-0"><?= htmlspecialchars(trans($this->title), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="text-muted small mb-0">System configuration and diagnostics overview</p>
        </div>
        <button onclick="window.location.reload()" class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center gap-2">
            <i class="fa fa-sync"></i> Refresh Status
        </button>
    </div>

    <div class="row g-4">
        <!-- Application Environment -->
        <div class="col-md-6 col-lg-4">
            <div class="zt-card p-4 h-100">
                <h5 class="fw-bold text-main mb-3"><i class="fa fa-server text-primary me-2"></i> Application</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">Environment</span>
                        <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($this->diagnostics['app_env']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">Debug Mode</span>
                        <span class="badge <?= $this->diagnostics['app_debug'] === 'Enabled' ? 'bg-warning' : 'bg-success' ?> rounded-pill">
                            <?= htmlspecialchars($this->diagnostics['app_debug']) ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">Server Software</span>
                        <span class="text-dark fw-bold"><?= htmlspecialchars($this->diagnostics['server_software']) ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- PHP Configuration -->
        <div class="col-md-6 col-lg-4">
            <div class="zt-card p-4 h-100">
                <h5 class="fw-bold text-main mb-3"><i class="fa fa-code text-info me-2"></i> PHP Configuration</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">PHP Version</span>
                        <span class="text-dark fw-bold"><?= htmlspecialchars($this->diagnostics['php_version']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">Memory Limit</span>
                        <span class="text-dark fw-bold"><?= htmlspecialchars($this->diagnostics['memory_limit']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">Max Execution Time</span>
                        <span class="text-dark fw-bold"><?= htmlspecialchars($this->diagnostics['max_execution_time']) ?>s</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Infrastructure -->
        <div class="col-md-6 col-lg-4">
            <div class="zt-card p-4 h-100">
                <h5 class="fw-bold text-main mb-3"><i class="fa fa-database text-success me-2"></i> Infrastructure</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">Database</span>
                        <?php if(strpos($this->diagnostics['db_connection'], 'success') !== false): ?>
                            <span class="badge bg-success rounded-pill"><i class="fa fa-check"></i> Connected</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-pill"><i class="fa fa-times"></i> Error</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">Disk Free</span>
                        <span class="text-dark fw-bold"><?= htmlspecialchars($this->diagnostics['disk_free_space']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0">
                        <span class="text-muted">Disk Total</span>
                        <span class="text-dark fw-bold"><?= htmlspecialchars($this->diagnostics['disk_total_space']) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
