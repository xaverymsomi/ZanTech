(function () {
    'use strict';

    angular.module('zantechApp')
        .controller('ModalProfileCtrl', [
            '$scope', '$uibModalInstance', '$http', '$compile', '$interval', '$filter', '$sce', 'toaster', 'ApiClient',
            function ($scope, $uibModalInstance, $http, $compile, $interval, $filter, $sce, toaster, ApiClient) {

                $scope.cancel = function () {
                    $uibModalInstance.dismiss('cancel');
                };

                $scope.ProcessingData = false;

                $scope.saveProfileOperation = function (url, action) {
                    $('.overlay').removeClass('hidden');
                    $scope.ProcessingData = true;
                    var post_url = app_url + "/" + url + "/" + action + "/";

                    $http({
                        method: 'POST',
                        url: post_url,
                        data: $scope.form,
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                    }).then(function (res) {
                        $('.overlay').addClass('hidden');
                        $scope.responseHandler(res.data);
                    }).finally(function () {
                        $scope.ProcessingData = false;
                    });
                };

                $scope.saveFormWithUploads = function (url, action, uploads) {
                    var post_url = app_url + "/" + url + "/" + action + "/";
                    var formdata = new FormData();
                    
                    angular.forEach($scope.form, function (value, key) {
                        formdata.append(key, value);
                    });

                    if (uploads && uploads.length > 0) {
                        angular.forEach(uploads, function (value) {
                            var element = document.getElementById(value);
                            if (element && element.files && element.files[0]) {
                                formdata.append(value, element.files[0]);
                            }
                        });
                    }

                    $http({
                        method: 'POST',
                        url: post_url,
                        data: formdata,
                        headers: { 'Content-Type': undefined }
                    }).then(function (res) {
                        $('.overlay').addClass('hidden');
                        $scope.responseHandler(res.data);
                    }).finally(function () {
                        $scope.ProcessingData = false;
                    });
                };

                $scope.responseHandler = function (response, reload = true) {
                    let code = Number(response.code || response.status);
                    let message = response.message || response.title || 'Operation completed';

                    if (code === 200 || code === 201) {
                        toaster.pop('success', 'Success', message);
                        setTimeout(function () {
                            $uibModalInstance.close();
                            if (reload) location.reload();
                        }, 1500);
                    } else {
                        toaster.pop('error', 'Error', message);
                    }
                };

                // ... (Additional specialized UI logic would continue here)
            }
        ]);
})();
