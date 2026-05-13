<div class="main zt-shell" ng-controller="menuController" id="mabrexMenuContainer" ng-init="getUserMenu()">

    <!-- OVERLAY (mobile only) -->
    <div class="zt-overlay" ng-click="closeSidebar()"></div>
    <?php
    $user = \Authentication\Auth::user() ?? [];
    $ztName = trim((string)($user['txt_name'] ?? ''));
    $ztUsername = trim((string)($user['txt_username'] ?? ''));
    $ztDomain = trim((string)($user['txt_domain'] ?? ''));
    $ztDisplayName = $ztName !== '' ? $ztName : ($ztUsername !== '' ? $ztUsername : 'Guest');
    $ztUserId = (int)($user['id'] ?? 0);
    $ztAvatarUrl = APP_DIR . '/assets/images/user.png';
    $ztAvatarFallback = 'https://ui-avatars.com/api/?name=' . rawurlencode($ztUsername !== '' ? $ztUsername : $ztDisplayName) . '&background=6366f1&color=fff';
    ?>

    <!-- FLOATING SEMESTER TAB -->
    <div class="zt-semester-tab">
        2025/2026-Semester II
    </div>

    <!-- SIDEBAR -->
    <aside class="zt-sidebar">
        <div class="zt-sidebar__header">
            <i class="fa-solid fa-graduation-cap zt-sidebar__cap" aria-hidden="true"></i>
            <div class="zt-sidebar__brand">
                <span class="zt-sidebar__brand-name">MU-ARMS</span>
                <span class="zt-sidebar__brand-version">2.0.0</span>
            </div>
        </div>

        <div class="zt-sidebar__content">
            <ul class="zt-menu list-unstyled">
                <li ng-if="menusLoading" class="px-3 py-2 small text-muted">Loading navigation…</li>
                <li ng-if="menusError && !menusLoading" class="px-3 py-2 small text-warning">Menu could not be loaded; showing defaults.</li>

                <li ng-repeat="section in menusGrouped track by ($index + '-' + (section.groupKey || 'x'))" class="zt-menu__section-wrap">
                    <div ng-if="section.label" class="zt-menu__label zt-menu__label--section" ng-class="{'mt-3': !$first}">{{ section.label }}</div>
                    <ul class="list-unstyled mb-0">
                        <li ng-repeat="menu in section.menus track by menu.id" class="zt-menu__group">
                            <div ng-if="menu.submenus.length">
                                <a href="javascript:void(0)" class="zt-menu__item zt-menu__item--parent"
                                   ng-class="{expanded: menu.isExpanded, 'zt-menu__item--open': menuParentActive(menu)}"
                                   ng-click="toggleMenu(menu); $event.preventDefault()">
                                    <span class="zt-menu__icon" ng-if="menu.icon"><i ng-class="menu.icon"></i></span>
                                    <span class="zt-menu__icon" ng-if="!menu.icon"><i class="fa-solid fa-folder"></i></span>
                                    <span class="zt-menu__text flex-grow-1">{{ menu.title || menu.name }}</span>
                                    <i class="zt-menu__chev fa-solid" ng-class="menu.isExpanded ? 'fa-chevron-down' : 'fa-chevron-left'" aria-hidden="true"></i>
                                </a>
                                <ul class="zt-menu__submenu list-unstyled mb-0" ng-show="menu.isExpanded">
                                    <li ng-repeat="sub in menu.submenus track by (menu.id + '-' + (sub.link || '') + '-' + $index)">
                                        <a href="javascript:void(0)" class="zt-menu__subitem"
                                           ng-class="{active: menuActive(sub.link)}"
                                           ng-click="openMenuLink(menu, sub)">{{ sub.title || sub.name }}</a>
                                    </li>
                                </ul>
                            </div>

                            <a href="javascript:void(0)" ng-if="!menu.submenus.length" class="zt-menu__item zt-menu__item--no-chev"
                               ng-class="{active: menuActive(menu.link)}"
                               ng-click="openMenuLink(menu, null)">
                                <span class="zt-menu__icon" ng-if="menu.icon"><i ng-class="menu.icon"></i></span>
                                <span class="zt-menu__icon" ng-if="!menu.icon"><i class="fa-solid fa-circle"></i></span>
                                <span class="zt-menu__text flex-grow-1">{{ menu.title || menu.name }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="zt-content-wrapper">

        <!-- TOP BAR -->
        <nav class="zt-topbar d-flex align-items-center">
            <div class="zt-topbar__left d-flex align-items-center">
                <button class="zt-icon-btn" type="button" ng-click="toggleSidebar()">
                    <i class="fa fa-ellipsis-v"></i>
                </button>
                <button class="zt-icon-btn" type="button" onclick="toggleAppsModal()" id="appsGridBtn" title="Apps">
                    <i class="fa-solid fa-table-cells-large"></i>
                </button>
            </div>

            <div class="zt-topbar__center d-none d-lg-block flex-grow-1 px-3">
                Welcome, <?= htmlspecialchars($ztDisplayName, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($ztDomain !== ''): ?>
                    - <span class="zt-status-red"><?= htmlspecialchars($ztDomain, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($ztName !== '' && $ztUsername !== '' && strcasecmp($ztName, $ztUsername) !== 0): ?>
                        <span class="text-muted"> - (<?= htmlspecialchars($ztUsername, ENT_QUOTES, 'UTF-8') ?>)</span>
                    <?php endif; ?>
                <?php elseif ($ztName !== '' && $ztUsername !== '' && strcasecmp($ztName, $ztUsername) !== 0): ?>
                    - <span class="zt-status-red"><?= htmlspecialchars($ztUsername, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="zt-topbar__right dropdown">
                <?php if (\Authentication\Auth::isLogged()): ?>
                <button type="button" class="zt-user-pill dropdown-toggle" id="ztUserMenuToggle" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true" aria-label="Account menu">
                    <img src="<?= htmlspecialchars($ztAvatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                         data-fallback-avatar="<?= htmlspecialchars($ztAvatarFallback, ENT_QUOTES, 'UTF-8') ?>"
                         onerror="this.onerror=null;this.src=this.dataset.fallbackAvatar;"
                         alt="<?= htmlspecialchars($ztDisplayName, ENT_QUOTES, 'UTF-8') ?>">
                    <span><?= htmlspecialchars($ztUsername !== '' ? $ztUsername : $ztDisplayName, ENT_QUOTES, 'UTF-8') ?> <i class="fa fa-chevron-down ms-1 zt-user-pill__chev" aria-hidden="true"></i></span>
                </button>

                <div class="dropdown-menu dropdown-menu-end zt-user-dropdown" aria-labelledby="ztUserMenuToggle">
                    <div class="zt-user-dropdown__hero">
                        <img src="<?= htmlspecialchars($ztAvatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                             data-fallback-avatar="<?= htmlspecialchars($ztAvatarFallback, ENT_QUOTES, 'UTF-8') ?>"
                             onerror="this.onerror=null;this.src=this.dataset.fallbackAvatar;"
                             alt="">
                    </div>
                    <div class="zt-user-dropdown__body">
                        <?php if ($ztUserId > 0): ?>
                        <a class="zt-user-dropdown__item" href="<?= APP_DIR ?>/User/profile/<?= $ztUserId ?>">
                            <span>Profile</span>
                            <i class="fa-regular fa-user" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                        <a class="zt-user-dropdown__item" href="<?= APP_DIR ?>/Settings">
                            <span>Settings</span>
                            <i class="fa-solid fa-gear" aria-hidden="true"></i>
                        </a>
                        <div class="zt-user-dropdown__divider"></div>
                        <a class="zt-user-dropdown__item" href="<?= APP_DIR ?>/User/password">
                            <span>Lock Account</span>
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        </a>
                        <a class="zt-user-dropdown__item zt-user-dropdown__item--danger" href="<?= APP_DIR ?>/<?= ZT_ROUTE_LOGOUT ?>">
                            <span>Log Out</span>
                            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- NOTIFICATION BELL -->
                <button class="zt-icon-btn has-badge position-relative" type="button">
                    <i class="fa fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; padding: 2px 4px;">0</span>
                </button>

                <!-- SECONDARY MENU -->
                <button class="zt-icon-btn" type="button">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
        </nav>

        <!-- APPS MODAL -->
        <div id="zt-apps-modal" class="zt-apps-modal" onclick="closeAppsModalOutside(event)">
            <div class="zt-apps-modal__panel">
                <div class="zt-apps-modal__header">
                    <span class="zt-apps-modal__title">APPS</span>
                    <button type="button" class="zt-apps-modal__close" onclick="toggleAppsModal()" aria-label="Close apps">
                        <span class="zt-apps-modal__close-ring" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                    </button>
                </div>
                <div class="zt-apps-modal__grid">
                    <?php
                    $appsModules = [
                        ['name'=>'Registration',    'icon'=>'book-open-reader', 'color'=>'#474a6b', 'link'=>'Registration'],
                        ['name'=>'Academic Records','icon'=>'file-circle-check','color'=>'#38a169', 'link'=>'Academic'],
                        ['name'=>'Field & Project', 'icon'=>'diagram-project',  'color'=>'#c0392b', 'link'=>'Field'],
                        ['name'=>'Accommodation',   'icon'=>'bed',              'color'=>'#d68910', 'link'=>'Accommodation'],
                    ];
                    foreach ($appsModules as $app):
                    ?>
                    <a href="<?= APP_DIR ?>/<?= $app['link'] ?>" class="zt-app-card" style="background:<?= $app['color'] ?>">
                        <i class="fa-solid fa-<?= $app['icon'] ?>"></i>
                        <span><?= $app['name'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- MAIN PAGE CONTENT -->
        <main class="flex-grow-1 zt-animate-fade-in" id="mabrexPageContent">
            <!-- BREADCRUMBS (Hidden on Dashboard) -->
            <div class="zt-breadcrumb-container px-4 py-3" ng-if="breadcrumbs.length > 0 && current !== 'dashboard'">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item" ng-repeat="crumb in breadcrumbs" ng-class="{active: $last}">
                            <a href="javascript:void(0)" ng-if="!$last" ng-click="loadPage(crumb.link, crumb.name)">
                                <i class="fa-solid fa-{{crumb.icon}} me-1" ng-if="crumb.icon"></i> {{crumb.name}}
                            </a>
                            <span ng-if="$last">
                                <i class="fa-solid fa-{{crumb.icon}} me-1" ng-if="crumb.icon"></i> {{crumb.name}}
                            </span>
                        </li>
                    </ol>
                </nav>
            </div>

            <?= $this->content ?? '' ?>
        </main>

        <!-- FOOTER -->
        <footer class="zt-footer">
            <div>Mzumbe University &copy; <?= date('Y') ?></div>
            <div>Designed & Developed by <a href="#" class="zt-footer-link-red">SDA-DICT</a></div>
        </footer>

    </div>
</div>

<script>
function toggleAppsModal() {
    var modal = document.getElementById('zt-apps-modal');
    if (modal) modal.classList.toggle('open');
}

function closeAppsModalOutside(event) {
    // Only close if clicking the overlay backdrop, not the panel
    if (event.target === document.getElementById('zt-apps-modal')) {
        document.getElementById('zt-apps-modal').classList.remove('open');
    }
}
</script>
