<?php

$requests = $this->requests ?? [];
$pendingCount = count($requests);
?>

<div class="container-fluid py-4">
    
    <!-- Header -->
    <?= $ui::sectionHeader(
        "Approval Dashboard", 
        "Review and authorize sensitive system operations.",
        ["<button class='btn btn-light border shadow-sm'><i class='fa fa-sync me-1'></i> Refresh</button>"]
    ) ?>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <?= $ui::statsCard("Pending Actions", $pendingCount, "fa-clock", "warning") ?>
        </div>
        <div class="col-md-4">
            <?= $ui::statsCard("Processed Today", 12, "fa-check-circle", "success") ?>
        </div>
        <div class="col-md-4">
            <?= $ui::statsCard("Avg. Turnaround", "1.5h", "fa-bolt", "info") ?>
        </div>
    </div>

    <!-- Main Content -->
    <?php ob_start(); ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4 py-3">Maker</th>
                    <th class="py-3">Module / Action</th>
                    <th class="py-3">Reference</th>
                    <th class="py-3">Date Triggered</th>
                    <th class="text-end pe-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="opacity-50 mb-3">
                                <i class="fa fa-clipboard-check fa-4x"></i>
                            </div>
                            <h6 class="text-muted">No pending approvals found.</h6>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                        <?= strtoupper(substr($r['maker_name'], 0, 1)) ?>
                                    </div>
                                    <span class="fw-semibold text-main"><?= htmlspecialchars($r['maker_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border px-2 py-1 mb-1">
                                    <?= htmlspecialchars($r['txt_module_name']) ?>
                                </span>
                                <div class="small text-muted fw-bold"><?= htmlspecialchars($r['activity_name']) ?></div>
                            </td>
                            <td>
                                <code class="bg-light p-1 rounded text-dark"><?= htmlspecialchars($r['reference'] ?? 'N/A') ?></code>
                            </td>
                            <td class="text-muted small">
                                <?= date('M d, Y', strtotime($r['date'])) ?><br>
                                <?= date('H:i A', strtotime($r['date'])) ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                    <button class="btn btn-white btn-sm border-end px-3" 
                                            onclick="approveAction(<?= $r['id'] ?>)" 
                                            title="Approve">
                                        <i class="fa fa-check text-success"></i>
                                    </button>
                                    <button class="btn btn-white btn-sm px-3" 
                                            onclick="rejectAction(<?= $r['id'] ?>)" 
                                            title="Reject">
                                        <i class="fa fa-times text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php 
    $content = ob_get_clean();
    echo $ui::card($content, "Pending Queue"); 
    ?>
</div>

<script>
function approveAction(id) {
    if (confirm("Are you sure you want to AUTHORIZE this action? It will be executed immediately.")) {
        fetch('<?= URL ?>/Approval/approve/' + id)
            .then(res => res.json())
            .then(data => {
                if (data.ok) location.reload();
                else alert(data.message);
            });
    }
}

function rejectAction(id) {
    if (confirm("Are you sure you want to REJECT this action?")) {
        fetch('<?= URL ?>/Approval/reject/' + id)
            .then(res => res.json())
            .then(data => {
                if (data.ok) location.reload();
                else alert(data.message);
            });
    }
}
</script>

<style>
.text-main { color: #2d3748; }
.bg-light { background-color: #f7fafc !important; }
.zt-card { background: #fff; border-radius: 1rem; }
.table thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #718096; border-bottom: none; }
.btn-white { background: #fff; border: none; }
.btn-white:hover { background: #f8f9fa; }
</style>
