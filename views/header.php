<!DOCTYPE html>
<html ng-app="zantechApp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?= defined('APP_NAME') ? htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') : 'Zantech' ?></title>

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Third Party CSS (CDNs for high performance & caching) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/angular-toaster/3.0.0/toaster.min.css">

    <!-- Unified Design System (Our Custom Bundle) -->
    <link rel="stylesheet" href="/<?= ltrim(APP_DIR . '/assets/css/zantech-ui.css', '/') ?>">

    <!-- Core Libraries (CDNs) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <!-- Bootstrap JS: required for data-bs-toggle="dropdown", modals, etc. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    
    <!-- AngularJS Ecosystem (CDNs) -->
    <script src="https://cdn.jsdelivr.net/npm/angular@1.8.2/angular.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/angular-animate@1.8.2/angular-animate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/angular-sanitize@1.8.2/angular-sanitize.min.js"></script>
    
    <!-- Plugins -->
    <script src="https://cdn.jsdelivr.net/npm/angular-ui-bootstrap@2.5.6/dist/ui-bootstrap-tpls.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/angular-filter@0.5.17/dist/angular-filter.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ng-file-upload@12.2.13/dist/ng-file-upload-all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ui-select@0.20.0/dist/select.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ui-select@0.20.0/dist/select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/angularjs-toaster@3.0.0/toaster.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/angularjs-toaster@3.0.0/toaster.min.css">

    <meta name="csrf-token" content="<?= \Authentication\Session::csrfToken() ?>">

    <?php
    $zt_js_app_url = '';
    if (defined('URL') && URL !== '') {
        $zt_js_app_url = rtrim((string)URL, '/');
    } else {
        $zt_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $zt_host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $zt_path = defined('APP_DIR') ? (string)APP_DIR : '';
        if ($zt_path !== '' && ($zt_path[0] ?? '') !== '/') {
            $zt_path = '/' . $zt_path;
        }
        $zt_js_app_url = rtrim($zt_scheme . '://' . $zt_host . $zt_path, '/');
    }
    ?>
    <script>window.app_url = <?= json_encode($zt_js_app_url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES) ?>;</script>
    <!-- Unified Application Logic (Custom JS Bundle with versioning) -->
    <!-- Modular Application Logic (Clean Architecture) -->
    <script src="/<?= ltrim(APP_DIR . '/assets/js/core/app.js?v=' . time(), '/') ?>"></script>
    <script src="/<?= ltrim(APP_DIR . '/assets/js/core/api.client.js?v=' . time(), '/') ?>"></script>
    <script src="/<?= ltrim(APP_DIR . '/assets/js/core/form.loader.js?v=' . time(), '/') ?>"></script>

    <!-- Layout Controllers -->
    <script src="/<?= ltrim(APP_DIR . '/assets/js/controllers/layout/menu.controller.js?v=' . time(), '/') ?>"></script>
    <script src="/<?= ltrim(APP_DIR . '/assets/js/controllers/layout/dashboard.controller.js?v=' . time(), '/') ?>"></script>

    <!-- Domain Module Controllers -->
    <script src="/<?= ltrim(APP_DIR . '/assets/js/controllers/modules/form.controller.js?v=' . time(), '/') ?>"></script>
    <script src="/<?= ltrim(APP_DIR . '/assets/js/controllers/modules/menu_manage.controller.js?v=' . time(), '/') ?>"></script>
    <script src="/<?= ltrim(APP_DIR . '/assets/js/controllers/modules/permission.controller.js?v=' . time(), '/') ?>"></script>
    <script src="/<?= ltrim(APP_DIR . '/assets/js/controllers/modules/profile.controller.js?v=' . time(), '/') ?>"></script>
    <script src="/<?= ltrim(APP_DIR . '/assets/js/controllers/modules/modal.controller.js?v=' . time(), '/') ?>"></script>
    <script src="/<?= ltrim(APP_DIR . '/assets/js/controllers/modules/report.controller.js?v=' . time(), '/') ?>"></script>

    <?= $this->dynamicStyles ?? '' ?>

</head>
<body>
