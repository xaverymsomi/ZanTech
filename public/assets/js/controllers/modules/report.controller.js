(function () {
    'use strict';

    angular.module('orynApp')
        .controller('reportCtrl', [
            '$scope', '$http', '$interval', '$compile', '$filter', 'ApiClient',
            function ($scope, $http, $interval, $compile, $filter, ApiClient) {

                $scope.ReportOptions = {
                    'Type': 1,
                    'StartDate': new Date(),
                    'EndDate': new Date(),
                    'Category': 0,
                    'FilterField': 0,
                    'GroupingField': 0,
                    'ReportTitle': 'GENERAL REPORT',
                    'FilterFieldValue': { 'Id': 0, 'Name': '', 'Type': 0 }
                };

                $scope.ReportIsOpen = false;

                $scope.getFormFields = function (report_type, value) {
                    $('.overlay').removeClass('hidden');
                    $scope.ReportOptions.Type = value;
                    
                    ApiClient.post(app_url + "/Report/get_form_fields", { 'report_type': report_type }, "Failed to load fields")
                        .then(function (data) {
                            $scope.ReportFilters = data.filters;
                            $scope.ReportGroupings = data.group_by;
                            $scope.ReportCategories = data.categories;
                            $scope.ReportOptions.ReportTitle = data.title;
                            // ... (Initialization logic)
                        })
                        .finally(function () {
                            $('.overlay').addClass('hidden');
                        });
                };

                $scope.generateReport = function () {
                    $('.overlay').removeClass('hidden');
                    var payload = {
                        'report_type': $scope.ReportOptions.Type,
                        'from_date': $scope.formatDate($scope.ReportOptions.StartDate),
                        'to_date': $scope.formatDate($scope.ReportOptions.EndDate),
                        'filter_criteria': $scope.ReportOptions.FilterField,
                        'group_criteria': $scope.ReportOptions.GroupingField,
                        'category': $scope.ReportOptions.Category,
                        'title': $scope.ReportOptions.ReportTitle,
                        'filter_value': $scope.ReportOptions.FilterFieldValue.Id
                    };

                    $http.post(app_url + "/Report/generate_report", payload)
                        .then(function (res) {
                            var data = res.data;
                            if (data.status === 200) {
                                $scope.ReportIsOpen = true;
                                $scope.writeHtmlTable(data.records);
                            }
                        })
                        .finally(function () {
                            $('.overlay').addClass('hidden');
                        });
                };

                $scope.formatDate = function (date) {
                    return $filter('date')(date, 'yyyy-MM-dd');
                };

                $scope.writeHtmlTable = function (data) {
                    // (Complex table rendering logic goes here, utilizing $compile)
                };
            }
        ]);
})();
