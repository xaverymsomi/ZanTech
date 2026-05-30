(function () {
    'use strict';

    angular
        .module('orynApp')
        .controller('menuController', ['$scope', '$rootScope', '$compile', '$window', '$http', 'toaster', function ($scope, $rootScope, $compile, $window, $http, toaster) {
            
            // Listen for changes from Menu Builder
            $rootScope.$on('navigationChanged', function() {
                $scope.getUserMenu();
            });

            /* ============================================================
               SIDEBAR TOGGLE (Mobile Drawer)
               - Adds/removes: body.zt-sidebar-open
            ============================================================ */
            $scope.sidebarOpen = false;

            function applySidebarClass() {
                document.body.classList.toggle('zt-sidebar-open', !!$scope.sidebarOpen);
            }

            $scope.toggleSidebar = function () {
                $scope.sidebarOpen = !$scope.sidebarOpen;
                // If desktop, toggle collapsed state instead of open state
                if (window.innerWidth >= 992) {
                    document.body.classList.toggle('zt-sidebar-collapsed');
                } else {
                    applySidebarClass();
                }
            };

            $scope.closeSidebar = function () {
                $scope.sidebarOpen = false;
                applySidebarClass();
            };

            $scope.closeSidebarOnMobile = function () {
                if (window.innerWidth <= 991) {
                    $scope.sidebarOpen = false;
                    applySidebarClass();
                }
            };

            // If user resizes to desktop, always close drawer
            angular.element($window).on('resize', function () {
                if ($window.innerWidth > 991 && $scope.sidebarOpen) {
                    $scope.sidebarOpen = false;
                    applySidebarClass();
                    $scope.$applyAsync();
                }
            });

            /* ============================================================
               THEME MANAGEMENT (Dark / Light)
            ============================================================ */
            $scope.isDark = localStorage.getItem('ZT_THEME') !== 'light';

            function applyTheme() {
                var theme = $scope.isDark ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('ZT_THEME', theme);
            }

            // Initial apply
            applyTheme();

            $scope.toggleTheme = function () {
                $scope.isDark = !$scope.isDark;
                applyTheme();
            };

            $scope.menus = [];
            $scope.menusGrouped = [];
            $scope.menusLoading = true;
            $scope.menusError = false;
            $scope.activePath = null;

            function normalizeMenuPath(link) {
                if (!link || link === '#') return null;
                var p = String(link).trim();
                if (p.indexOf('/') !== 0) p = '/' + p;
                var q = p.indexOf('?');
                if (q >= 0) p = p.substring(0, q);
                if (p.length > 1) p = p.replace(/\/+$/, '');
                return p.toLowerCase();
            }

            $scope.menuActive = function (link) {
                var n = normalizeMenuPath(link);
                if (!n || !$scope.activePath) return false;
                return n === $scope.activePath;
            };

            $scope.menuParentActive = function (menu) {
                if (!menu || !menu.submenus || !menu.submenus.length) return false;
                for (var pi = 0; pi < menu.submenus.length; pi++) {
                    if ($scope.menuActive(menu.submenus[pi].link)) return true;
                }
                return false;
            };

            $scope.syncMenuExpandedState = function () {
                if (!$scope.menus || !$scope.menus.length) return;
                angular.forEach($scope.menus, function (m) {
                    m.isExpanded = false;
                    if (m.submenus && m.submenus.length && $scope.activePath) {
                        for (var si = 0; si < m.submenus.length; si++) {
                            if ($scope.menuActive(m.submenus[si].link)) {
                                m.isExpanded = true;
                                break;
                            }
                        }
                    }
                });
            };

            $scope.getFallbackDashboardMenu = function () {
                return {
                    id: 'dashboard',
                    name: 'Dashboard',
                    link: '/Dashboard',
                    title: 'Dashboard',
                    icon: 'fa-solid fa-gauge-high',
                    sidebarGroup: '',
                    submenus: []
                };
            };

            $scope.rebuildMenusGrouped = function () {
                $scope.menusGrouped = [];
                var list = $scope.menus || [];
                var chunk = null;
                angular.forEach(list, function (m) {
                    var g = (m.sidebarGroup !== undefined && m.sidebarGroup !== null) ? String(m.sidebarGroup).trim() : '';
                    if (!chunk || chunk.groupKey !== g) {
                        chunk = { groupKey: g, label: (g === '' ? null : g), menus: [] };
                        $scope.menusGrouped.push(chunk);
                    }
                    chunk.menus.push(m);
                });
            };

            $scope.getUserMenu = function () {
                $scope.menusLoading = true;
                $scope.menusError = false;
                var base = ($window.app_url || '').replace(/\/+$/, '');
                var url = base + '/Menu/getUserMenus';
                $http.get(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (res) {
                    $scope.menusLoading = false;
                    var payload = res.data || {};
                    if (payload.ok && angular.isArray(payload.data)) {
                        $scope.menus = payload.data;
                    } else {
                        $scope.menus = [];
                    }
                    if (!$scope.menus.length) {
                        $scope.menus = [$scope.getFallbackDashboardMenu()];
                    }
                    try {
                        var cl = $window.localStorage.getItem('CurrentLink');
                        if (cl) {
                            $scope.activePath = normalizeMenuPath(cl.indexOf('/') === 0 ? cl : '/' + cl);
                        }
                    } catch (e) { /* ignore */ }
                    angular.forEach($scope.menus, function (m) {
                        if (typeof m.isExpanded === 'undefined') m.isExpanded = false;
                    });
                    $scope.syncMenuExpandedState();
                    $scope.rebuildMenusGrouped();
                }).catch(function () {
                    $scope.menusLoading = false;
                    $scope.menusError = true;
                    $scope.menus = [$scope.getFallbackDashboardMenu()];
                    $scope.syncMenuExpandedState();
                    $scope.rebuildMenusGrouped();
                });
            };

            $scope.loadPage = function (_link, _title, _id) {
                if (!_link || _link === 'undefined' || _link === 'null') return;
                if (_link === '#') return;

                var pathForActive = String(_link).startsWith('/') ? _link : '/' + _link;
                $scope.activePath = normalizeMenuPath(pathForActive);
                $scope.syncMenuExpandedState();

                localStorage.setItem('CurrentLink', _link);
                var base = (window.app_url || '').replace(/\/+$/, '');
                var path = String(_link).startsWith('/') ? _link : '/' + _link;
                var finalUrl = base + path;

                $('.overlay').removeClass('hidden');

                $http.get(finalUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (res) {
                    $('.overlay').addClass('hidden');
                    var html = res.data;
                    var tempDiv = $('<div>').append($.parseHTML(html));
                    var pageContent = tempDiv.find('#page-content');
                    var targetHtml = pageContent.length ? pageContent.html() : html;

                    var contentArea = $('#mabrexPageContent');
                    contentArea.html(targetHtml);
                    $compile(contentArea.contents())($scope);

                    if (_title) $window.document.title = 'Oryn - ' + _title;
                    $window.history.pushState({ path: _link }, '', finalUrl);
                }).catch(function (err) {
                    $('.overlay').addClass('hidden');
                    console.error('Navigation error:', err);
                    toaster.pop('error', 'Navigation', 'Failed to load page');
                });
            };

            $scope.openMenuLink = function (menu, sub) {
                var item = sub || menu;
                if (!item || !item.link || item.link === '#') return;

                $scope.loadPage(item.link, item.title || item.name);
                $scope.closeSidebarOnMobile();
            };

            $scope.toggleMenu = function (menu) {
                if (menu.isExpanded) {
                    menu.isExpanded = false;
                    return;
                }
                angular.forEach($scope.menus, function (m) {
                    m.isExpanded = false;
                });
                menu.isExpanded = true;
            };

        }]);
})();
