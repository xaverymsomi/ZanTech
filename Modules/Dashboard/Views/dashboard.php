<div class="zt-animate-fade-in zt-dashboard-page">
    
    <!-- Cinematic Header -->
    <div class="zt-dashboard-header">
        <h1>Dashboard</h1>
    </div>

    <!-- Launcher Grid -->
    <div class="zt-launcher-grid">
        <?php foreach ($this->modules ?? [] as $module): ?>
            <a href="<?= APP_DIR ?>/<?= $module['link'] ?>" class="zt-launcher-card">
                <div class="zt-launcher-card__icon" style="color: <?= $module['color'] ?>;">
                    <i class="fa-solid fa-<?= $module['icon'] ?>"></i>
                </div>
                <div class="zt-launcher-card__title">
                    <?= $module['name'] ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

</div>
