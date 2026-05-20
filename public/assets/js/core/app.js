(function () {
    'use strict';

    // 1. Sub-Module Definitions (Must come before zantechApp)
    angular.module('create.modal', ['ui.bootstrap', 'toaster', 'angular.filter', 'ngFileUpload']);
    angular.module('dashboard.modal', ['ui.bootstrap', 'toaster']);
    angular.module('permission.modal', ['ui.bootstrap', 'toaster', 'angular.filter']);
    angular.module('profile.modal', ['ui.bootstrap', 'toaster']);
    angular.module('report.modal', ['ui.bootstrap', 'toaster']);

    // 2. Main Application Module
    angular.module('zantechApp', [
        'toaster',
        'ngAnimate',
        'ngSanitize',
        'ui.select',
        'ui.bootstrap',
        'angular.filter',
        'ngFileUpload',
        'create.modal',
        'profile.modal',
        'dashboard.modal',
        'permission.modal',
        'report.modal'
    ])
        .config(['$locationProvider', '$httpProvider', function ($locationProvider, $httpProvider) {
            $locationProvider.html5Mode({ enabled: true, requireBase: false, rewriteLinks: false });

            // CSRF Interceptor
            $httpProvider.interceptors.push(function () {
                return {
                    request: function (config) {
                        var token = document.querySelector('meta[name="csrf-token"]');
                        if (token) {
                            config.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
                        }
                        return config;
                    }
                };
            });
        }])
        .config(['$compileProvider', function ($compileProvider) {
            $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|blob):/);
        }]);

    window.app_url = window.app_url || 'http://localhost:9070';
    window.app_folder = '';

})();
