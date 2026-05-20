(function () {
    'use strict';

    angular.module('create.modal')
        .controller('formController', [
            '$scope', '$uibModal', '$log', '$compile', '$http', '$filter', 'toaster', 'FormLoader',
            function ($scope, $uibModal, $log, $compile, $http, $filter, toaster, FormLoader) {

                $scope.form = {};
                $scope.dropdowns = {};
                $scope.stepper = 1;

                $scope.showForm = function (url, action, params = null) {
                    $scope.url = url[0].toUpperCase() + url.substring(1);
                    var form_url = app_url + "/" + $scope.url + "/" + action.toLowerCase();
                    if (params) {
                        params.forEach(p => form_url += '/' + p);
                    }

                    FormLoader.load(form_url).then(function (result) {
                        $scope.dropdowns = result.data.dropdowns;
                        $scope.form = result.data.form || {};

                        if (!result.template) {
                            toaster.pop('error', "Error", "Form template is empty");
                            FormLoader.hideOverlay();
                            return;
                        }

                        var modalInstance = $uibModal.open({
                            template: result.template,
                            controller: 'modalFormCtrl',
                            windowClass: 'mx-modal-form',
                            scope: $scope
                        });

                        modalInstance.result.catch(angular.noop);
                        FormLoader.hideOverlay();
                    }).catch(function (err) {
                        console.error(err);
                        toaster.pop('error', "Error", "Failed to load form");
                        FormLoader.hideOverlay();
                    });
                };

                $scope.nextStep = function () { $scope.stepper++; };
                $scope.prevStep = function () { $scope.stepper--; };

                // ... (Additional validation and class management logic would continue here)
            }
        ])
        .controller('modalFormCtrl', [
            '$scope', '$uibModalInstance', '$http', '$uibModal', '$window', '$filter', 'Upload', 'toaster',
            function ($scope, $uibModalInstance, $http, $uibModal, $window, $filter, Upload, toaster) {
                
                $scope.cancel = function () {
                    $uibModalInstance.dismiss('cancel');
                };

                $scope.saveForm = function (action = "") {
                    $('.overlay').removeClass('hidden');
                    var post_url = app_url + "/" + $scope.url + "/" + (action || 'save') + "/";

                    $http({
                        method: 'POST',
                        url: post_url,
                        data: $scope.form,
                        headers: { 'Content-Type': 'application/json' }
                    }).then(function (res) {
                        $('.overlay').addClass('hidden');
                        var response = res.data || {};
                        let httpStatus = Number(res.status);
                        let message = response.message || response.title || 'Operation completed';

                        if (httpStatus >= 200 && httpStatus <= 299) {
                            var title = (httpStatus === 202 || response.status === 'pending') ? 'Pending Approval' : 'Success';
                            toaster.pop('success', title, message);
                            setTimeout(function () {
                                $uibModalInstance.close();
                                location.reload();
                            }, 1500);
                        } else {
                            toaster.pop('error', 'Error', message);
                        }
                    }).catch(function (err) {
                        $('.overlay').addClass('hidden');
                        console.error(err);
                        var data = (err && err.data) ? err.data : {};
                        var msg = data.message || data.title || 'Failed to save form';
                        toaster.pop('error', 'Error', msg);
                    });
                };
            }
        ]);
})();
