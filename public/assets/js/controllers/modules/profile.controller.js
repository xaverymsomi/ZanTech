(function () {
    'use strict';

    angular.module('profile.modal')
        .controller('profileController', [
            '$scope', '$uibModal', '$log', '$timeout', '$http', '$compile', '$interval', '$filter', 'toaster', '$sce', 'FormLoader',
            function ($scope, $uibModal, $log, $timeout, $http, $compile, $interval, $filter, toaster, $sce, FormLoader) {
                
                $scope.currentsms = 1;
                $scope.form = {};
                $scope.dropdowns = {};
                $scope.extraControl = {};
                $scope.autoCompleteSelectOptions = {};
                
                // Dynamic Theme Detection
                $scope.isDarkMode = document.body.classList.contains('dark') || 
                                   document.documentElement.classList.contains('dark-theme');

                var time_data = [
                    'From Time', 'To Time', 'Departure Time', 'Collection Time', 'Opening Hour', 'Closing Hour', 'last_updated', 'dat_dose_date'
                ];
                var time_urls = [
                    'Application', 'Center', 'Vaccination'
                ];
                
                $scope.iconPreviewClasses = function (raw) {
                    raw = String(raw || '').trim();
                    if (!raw) return 'fa-solid fa-fw fa-circle text-muted opacity-25';
                    if (raw.indexOf('fa-') !== -1) return 'fa-fw ' + raw;
                    return 'fa-solid fa-fw fa-' + raw;
                };

                $scope.menuParentRowFilter = function (item) {
                    if (!$scope.form || !$scope.form.int_menu_record_id) return true;
                    // Prevent circular reference (self as parent)
                    return Number(item.id) !== Number($scope.form.int_menu_record_id);
                };

                $scope.showProfile = function (url, id) {
                    $scope.url = url;
                    $scope.current_tab = url;
                    $scope.is_profile_tab = true;
                    var formURL = `${app_url}/${url}/profile/${id}`;

                    FormLoader.load(formURL).then(function (result) {
                        var template = result.template;
                        $scope.tabs = result.data.tabs;
                        $scope.initial_tab_data = result.data.initial;
                        $scope.hidden_columns = result.data.hidden_columns;
                        
                        if (time_urls.includes(url)) {
                            time_data.forEach(function (item) {
                                if ($scope.initial_tab_data[item]) {
                                    $scope.initial_tab_data[item] = new Date($scope.initial_tab_data[item]);
                                }
                            });
                        }

                        var modalInstance = $uibModal.open({
                            template: template,
                            controller: 'ModalProfileCtrl',
                            windowClass: 'mx-modal-form',
                            scope: $scope
                        });

                        modalInstance.result.catch(angular.noop);


                        FormLoader.hideOverlay();
                    }).catch(function (err) {
                        console.error(err);
                        toaster.pop('error', "Error", "Failed to load profile");
                        FormLoader.hideOverlay();
                    });
                };

                $scope.showActionForm = function (id, url, action) {
                    var base = window.app_url || '';
                    var formURL = `${base}/${url}/${action.toLowerCase()}/${id}`;
                    console.log("[ProfileCtrl] showActionForm triggered:", {id, url, action, formURL});

                    FormLoader.load(formURL).then(function (result) {
                        console.log("[ProfileCtrl] Form loaded successfully. Opening modal...");
                        $scope.dropdowns = result.data.dropdowns;
                        $scope.form = result.data.form;

                        var modalInstance = $uibModal.open({
                            template: result.template,
                            controller: 'ModalProfileCtrl',
                            windowClass: 'mx-modal-form zt-animate-fade-in',
                            scope: $scope,
                            backdrop: 'static'
                        });

                        modalInstance.result.catch(angular.noop);
                        FormLoader.hideOverlay();
                    }).catch(function (err) {
                        console.error("[ProfileCtrl] Failed to load form:", err);
                        toaster.pop('error', "Error", "Failed to load form");
                        FormLoader.hideOverlay();
                    });
                };

                // ... (Additional analytics and helper logic would continue here)
            }
        ]);
})();
