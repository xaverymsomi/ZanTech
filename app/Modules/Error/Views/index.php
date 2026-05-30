<?php
/**
 * ============================================================================
 *  ORYN ERROR VIEW (Bootstrap-enhanced)
 * ============================================================================
 *  - Uses Bootstrap + Bootstrap Icons
 *  - Icon + color explain the error visually
 *  - Backend controls icon via $this->icon
 * ============================================================================
 */

$iconClass = $this->icon ?? 'bi-exclamation-triangle-fill';
$colorClass = 'text-danger';

// Map icon → color meaning (purely visual, no logic)
if (str_contains($iconClass, 'lock')) {
    $colorClass = 'text-warning';   // auth / permission
} elseif (str_contains($iconClass, 'shield')) {
    $colorClass = 'text-danger';    // security
} elseif (str_contains($iconClass, 'info')) {
    $colorClass = 'text-info';      // informational
} elseif (str_contains($iconClass, 'check')) {
    $colorClass = 'text-success';   // success (rare)
}
?>

<div class="container vh-100 d-flex align-items-center justify-content-center" ng-non-bindable>
    <div class="card shadow-lg border-0" style="max-width: 520px; width:100%;">
        <div class="card-body text-center p-5">

            <!-- ICON -->
            <div class="mb-4">
                <i class="bi <?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?> <?= $colorClass ?>"
                   style="font-size: 4rem;"></i>
            </div>

            <!-- TITLE -->
            <h2 class="fw-bold mb-3">
                <?= htmlspecialchars($this->title ?? 'Error', ENT_QUOTES, 'UTF-8') ?>
            </h2>

            <!-- MESSAGE -->
            <?php if (!empty($this->msg)): ?>
                <p class="text-muted mb-2">
                    <?= htmlspecialchars($this->msg, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <!-- SUB MESSAGE -->
            <?php if (!empty($this->sub)): ?>
                <p class="small text-secondary">
                    <?= htmlspecialchars($this->sub, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <!-- ACTIONS -->
            <div class="mt-4 d-flex gap-2 justify-content-center">
                <a href="/" class="btn btn-primary">
                    <i class="bi bi-house-door me-1"></i> Home
                </a>

                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Go Back
                </button>
            </div>

            <!-- REQUEST ID (debug-friendly, production-safe) -->
            <?php if (!empty($_SERVER['ZT_REQUEST_ID'])): ?>
                <div class="mt-4 small text-muted">
                    Ref: <?= htmlspecialchars($_SERVER['ZT_REQUEST_ID'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
