(function () {
    'use strict';

    angular.module("dashboard.modal")
        .controller("AdminDashboardCtrl", ['$scope', '$http', function ($scope, $http) {

            var url = app_url + '/Dashboard/getAdminData';
            $scope.isLoaded = false;

            $scope.fetchData = function () {
                $http.get(url).then(function (response) {
                    if (response.data && response.data.data) {
                        $scope.dashboardData = response.data.data;
                        $scope.isLoaded = true;
                    }
                });
            };

            // Initial load
            $scope.fetchData();

            // Auto-refresh every 2 minutes
            var interval = setInterval($scope.fetchData, 120000);

            $scope.$on('$destroy', function () {
                clearInterval(interval);
            });
        }]);
})();
