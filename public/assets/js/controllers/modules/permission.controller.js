(function () {
    'use strict';

    angular.module('permission.modal')
        .controller('permissionCtrl', [
            '$scope', '$interval', '$window', 'toaster', 'ApiClient',
            function ($scope, $interval, $window, toaster, ApiClient) {

                $scope.activetab = 1;
                $scope.account_detail = { groups: [], users: [], sections: [], allPermissions: [] };
                
                $scope.form = {};
                $scope.new_group_permission = { group_id: null };
                $scope.new_user_group = { user_id: null };
                $scope.new_user_permission = { user_id: null };

                $scope.group_permissions = [];
                $scope.group_permission_flag = false;
                $scope.user_group_flag = false;
                $scope.user_permission_flag = false;
                $scope.groups = [];
                $scope.user_permissions = [];
                $scope.sectionCheck = {};

                $scope.setActiveTab = function (tab) {
                    $scope.activetab = tab;
                };

                $scope.isActiveTab = function (tab) {
                    return $scope.activetab === tab;
                };

                $scope.getData = function () {
                    ApiClient.post(window.app_url + "/Permission/loadData", {}, "Failed to load bootstrap data")
                        .then(function (res) {
                            $scope.account_detail = res.data || res || {};
                        });
                };

                $scope.getPermissions = function (action, id) {
                    if (!id) {
                        $scope.group_permission_flag = false;
                        $scope.user_group_flag = false;
                        $scope.user_permission_flag = false;
                        return;
                    }
                    
                    var payload = { id: id };
                    if (action === 'getUserGroups' || action === 'getUserPermissions') {
                        var parts = String(id).split(',');
                        payload = { id: parts[0], domain: parts[1] };
                    } else if (action === 'getGroupPermissions') {
                        payload = { group_id: id };
                    }

                    ApiClient.post(window.app_url + "/Permission/" + action, payload, "Failed to load details")
                        .then(function (res) {
                            var data = res.data || res || [];
                            if (action === 'getGroupPermissions') {
                                $scope.group_permissions = data;
                                $scope.group_permission_flag = true;
                            } else if (action === 'getUserGroups') {
                                $scope.groups = data;
                                $scope.user_group_flag = true;
                            } else if (action === 'getUserPermissions') {
                                $scope.user_permissions = data;
                                $scope.user_permission_flag = true;
                            }
                        });
                };

                $scope.saveForm = function (module, method) {
                    ApiClient.post(window.app_url + "/" + module + "/" + method, $scope.form, "Failed to save record")
                        .then(function (res) {
                            toaster.pop('success', 'Success', res.message || 'Operation successful');
                            $scope.form = {};
                            $scope.getData();
                        });
                };

                $scope.saveTableData = function (tableId, method, id) {
                    if (!id) return;
                    
                    var newData = [];
                    var parts = String(id).split(',');
                    var payload = { id: parts[0], domain: parts[1] || '' };

                    if (method === 'postGroupPermission') {
                        newData = $scope.group_permissions.map(function (p) {
                            return [p.check ? 1 : 0, Number(p.permission_id)];
                        });
                        payload = { id: parts[0] }; // group id
                    } else if (method === 'postUserGroup') {
                        newData = $scope.groups.map(function (g) {
                            return [g.check ? 1 : 0, Number(g.group_id)];
                        });
                    } else if (method === 'postUserPermission') {
                        newData = $scope.user_permissions.map(function (p) {
                            return [p.check ? 1 : 0, Number(p.permission_id)];
                        });
                    }

                    payload.new_data = newData;

                    ApiClient.post(window.app_url + "/Permission/" + method, payload, "Failed to update permissions")
                        .then(function (res) {
                            toaster.pop('success', 'Security', res.message || 'Permissions updated');
                        });
                };

                $scope.toggleSection = function(sectionList, status) {
                    angular.forEach(sectionList, function(p) {
                        p.check = status;
                    });
                };

            }
        ]);
})();
