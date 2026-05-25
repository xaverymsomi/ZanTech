<div class="zt-animate-fade-in zt-dashboard-page">
    
    <!-- Cinematic Header -->
    <div class="zt-dashboard-header">
        <h1>Dashboard</h1>
    </div>

    <!-- Launcher Grid -->
    <div class="zt-launcher-grid">
        <?php foreach ($this->modules ?? [] as $module): ?>
            <a href="<?= APP_DIR ?>/<?= htmlspecialchars($module['link'], ENT_QUOTES, 'UTF-8') ?>" class="zt-launcher-card">
                <div class="zt-launcher-card__icon" style="color: <?= htmlspecialchars($module['color'], ENT_QUOTES, 'UTF-8') ?>;">
                    <i class="fa-solid fa-<?= htmlspecialchars($module['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                </div>
                <div class="zt-launcher-card__title">
                    <?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

</div>
