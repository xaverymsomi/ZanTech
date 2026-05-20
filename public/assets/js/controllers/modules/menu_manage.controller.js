(function () {
    'use strict';

    angular.module('zantechApp')
        .controller('menuManageCtrl', [
            '$scope', '$rootScope', '$window', '$http', '$timeout', '$compile', '$uibModal', 'toaster',
            function ($scope, $rootScope, $window, $http, $timeout, $compile, $uibModal, toaster) {

                $scope.dropdowns = { int_parent_ids: [], all_menus: [] };

                $scope.new_menu_form = {
                    relation: 0, // 0 main, 1 sub
                    txt_name: '',
                    txt_icon: '',
                    txt_sidebar_group: '',
                    int_parent: '',
                    int_position: 1,
                    txt_link: '#',
                    txt_title: ''
                };



                $scope.loadingMenus = false;
                $scope.savingMenu = false;

                $scope.getMenuDropdowns = function (dd) {
                    $scope.dropdowns = dd || {};
                    if (!$scope.dropdowns.int_parent_ids) $scope.dropdowns.int_parent_ids = [];
                    if (!$scope.dropdowns.all_menus) $scope.dropdowns.all_menus = [];
                };

                $scope.resetForm = function () {
                    $scope.new_menu_form = {
                        relation: 0,
                        txt_name: '',
                        txt_icon: '',
                        txt_sidebar_group: '',
                        int_parent: '',
                        int_position: ($scope.dropdowns.next_top_position || 1),
                        txt_link: '#',
                        txt_title: ''
                    };
                    if ($scope.new_menu) {
                        $scope.new_menu.$setPristine();
                        $scope.new_menu.$setUntouched();
                    }
                };

                $scope.$watch('new_menu_form.relation', function (v) {
                    v = Number(v);
                    if (v === 0) {
                        $scope.new_menu_form.int_parent = '';
                        if (!$scope.new_menu_form.txt_link) $scope.new_menu_form.txt_link = '#';
                    } else {
                        $scope.new_menu_form.txt_icon = '';
                        $scope.new_menu_form.txt_sidebar_group = '';
                        if (!$scope.new_menu_form.txt_link || $scope.new_menu_form.txt_link === '#') {
                            $scope.new_menu_form.txt_link = '';
                        }
                    }
                });

                $scope.$watch('new_menu_form.int_parent', function (pid) {
                    if (Number($scope.new_menu_form.relation) === 1 && pid) {
                        var posMap = $scope.dropdowns.next_child_position_by_parent || {};
                        var nextPos = posMap[pid];
                        if (nextPos) {
                            $scope.new_menu_form.int_position = nextPos;
                        }
                    }
                });

                $scope.getAllMenus = function () {
                    $scope.loadingMenus = true;
                    $http.get(($window.app_url || '') + '/Menu/get_all_menus')
                        .then(function (res) {
                            if (res.data && Array.isArray(res.data.data)) {
                                $scope.dropdowns.all_menus = res.data.data;
                            }
                            if (res.data && res.data.dropdowns) {
                                // Update dropdowns and other metadata
                                var newDD = res.data.dropdowns;
                                $scope.dropdowns.int_parent_ids = newDD.int_parent_ids || [];
                                $scope.dropdowns.next_top_position = newDD.next_top_position || 1;
                                $scope.dropdowns.next_child_position_by_parent = newDD.next_child_position_by_parent || {};
                                
                                // Auto-update next position if form is currently in "Main" mode
                                if (Number($scope.new_menu_form.relation) === 0) {
                                    $scope.new_menu_form.int_position = $scope.dropdowns.next_top_position;
                                }
                            }
                        })
                        .finally(function () {
                            $scope.loadingMenus = false;
                        });
                };

                $scope.saveMenu = function () {
                    if ($scope.savingMenu) return;
                    var f = $scope.new_menu_form || {};
                    if (!f.txt_name || !f.txt_title) {
                        toaster.pop('error', 'Validation', 'Name and Title are required');
                        return;
                    }

                    var isSub = Number(f.relation) === 1;
                    var payload = {
                        func_name: 'saveMenu',
                        new_data: $scope.new_menu_form
                    };

                    $http.post(($window.app_url || '') + '/Menu/saveMenu', payload).then(function (response) {
                        var data = response.data;
                        if (data.status == 200 || data.code == 200) {
                            toaster.pop('success', 'Success', 'Menu item created');
                            $scope.resetForm();
                            $scope.getAllMenus();
                            $rootScope.$broadcast('navigationChanged');
                        } else {
                            toaster.pop('error', 'Error', data.message || 'Failed to save menu');
                        }
                    }).catch(function (err) {
                        console.error(err);
                        toaster.pop('error', 'Error', 'System error occurred');
                    }).finally(function () {
                        $scope.savingMenu = false;
                    });
                };

                $scope.deleteMenu = function (id, name) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Deleting '" + name + "' will also remove its submenus!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!',
                        background: $scope.isDarkMode ? '#1e293b' : '#fff',
                        color: $scope.isDarkMode ? '#fff' : '#000'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $http.post(($window.app_url || '') + '/Menu/deleteMenu', { id: id }).then(function (response) {
                                if (response.data.status == 200 || response.data.code == 200) {
                                    toaster.pop('success', 'Deleted', 'Menu item removed');
                                    $scope.getAllMenus();
                                    $rootScope.$broadcast('navigationChanged');
                                } else {
                                    toaster.pop('error', 'Error', response.data.message || 'Delete failed');
                                }
                            });
                        }
                    });
                };


            }
        ]);
})();
