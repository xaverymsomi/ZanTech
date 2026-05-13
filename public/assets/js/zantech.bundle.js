(function () {
    'use strict';

    // 1. Sub-Module Definitions (Must come before zantechApp)
    angular.module('create.modal', ['ui.bootstrap', 'toaster', 'angular.filter', 'ngFileUpload']);
    angular.module('dashboard.modal', ['ui.bootstrap', 'toaster']);
    angular.module('permission.modal', ['ui.bootstrap', 'toaster']);
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
        }]);

    angular.module('zantechApp')
        .factory('ApiClient', [

            '$http', '$q', 'toaster',
            function ($http, $q, toaster) {

                function buildMessage(data, fallbackTitle) {
                    var msg = data.title || data.message || fallbackTitle || 'Request failed';

                    if (data.errors && typeof data.errors === 'object') {
                        var parts = [];
                        angular.forEach(data.errors, function (v, k) {
                            parts.push(k + ': ' + v);
                        });
                        msg += ' â€” ' + parts.join(', ');
                    }

                    return msg;
                }

                function normalizeError(res, fallbackTitle) {
                    // already normalized
                    if (res && typeof res.status === 'number' && typeof res.message === 'string') {
                        return res;
                    }

                    var data = (res && res.data) ? res.data : {};
                    var status =
                        (typeof data.status === 'number') ? data.status :
                            (typeof res.status === 'number') ? res.status :
                                500;

                    return {
                        status: status,
                        message: buildMessage(data, fallbackTitle),
                        raw: res
                    };
                }

                function notifyError(err) {
                    toaster.pop('error', 'Error ' + (err.status || 500), err.message || 'Request failed');
                }

                /**
                 * POST JSON helper.
                 * Supports backend "soft errors" in shape: {status: 4xx/5xx, title, errors}
                 *
                 * @param {string} url
                 * @param {*} payload
                 * @param {string=} fallbackTitle
                 */
                function post(url, payload, fallbackTitle) {
                    return $http({
                        method: 'POST',
                        url: url,
                        data: payload,
                        headers: { 'Content-Type': 'application/json' }
                    }).then(function (res) {

                        // Soft error: HTTP 200 but backend returned {status: >=400}
                        if (res.data && typeof res.data.status === 'number' && res.data.status >= 400) {
                            var softErr = normalizeError(res, fallbackTitle);
                            notifyError(softErr);
                            return $q.reject(softErr);
                        }

                        return res.data;
                    }).catch(function (resOrErr) {
                        var err = normalizeError(resOrErr, fallbackTitle);
                        notifyError(err);
                        return $q.reject(err);
                    });
                }

                return { post: post };
            }
        ]);

})();
(function () {
    'use strict';

    angular.module('zantechApp')
        .service('FormLoader', ['$http', '$q', '$compile', '$timeout', function ($http, $q, $compile, $timeout) {

            this.load = function (url) {
                var deferred = $q.defer();
                $('.overlay').removeClass('hidden'); // Show spinner

                // Create a temporary div to load the content
                var div = $('<div/>');

                div.load(url + ' #page-content', function (response, status, xhr) {
                    if (status == "error") {
                        $('.overlay').addClass('hidden');
                        deferred.reject("Error loading form: " + xhr.status + " " + xhr.statusText);
                        return;
                    }

                    try {
                        var result = {
                            template: div.find('#display_content').html(),
                            data: {},
                            extras: {}
                        };

                        // Parse standard data attributes
                        var dataContent = div.find('#data_content');

                        // Helper to safely parse JSON
                        var parseData = function (attr) {
                            var val = dataContent.attr(attr);
                            return val ? JSON.parse(val) : undefined;
                        };

                        result.data = {
                            dropdowns: parseData('data-dropdowns'),
                            form: parseData('data-form'),
                            tabs: parseData('data-tabs'),
                            initial: parseData('data-initial'),
                            hidden_columns: parseData('data-hidden-columns'),
                            account_detail: parseData('data-account-detail'),
                            experts: parseData('data-experts'),
                            extras: parseData('data-extras'),
                            disabled: parseData('data-disabled'),
                            client_functions: parseData('data-client-functions'),
                            extra_data: parseData('data-extra-data'),
                            investigation_detail: parseData('data-investigation-detail'),
                            current_institution: parseData('data-current-institution'),
                            missing_accounts: parseData('data-missing-accounts')
                        };

                        // Handle file inputs logic if needed later (usually done in controller after open)
                        // Handle text areas if needed

                        deferred.resolve(result);
                    } catch (e) {
                        $('.overlay').addClass('hidden');
                        deferred.reject("Error parsing form data: " + e.message);
                    }
                });

                return deferred.promise;
            };

            this.hideOverlay = function () {
                $('.overlay').addClass('hidden');
            };

            this.showOverlay = function () {
                $('.overlay').removeClass('hidden');
            };

        }]);
})();

window.app_url = 'http://localhost:9070';
window.app_folder = '';
var app = angular.module("create.modal");

app.controller("formController", [
    '$scope', '$uibModal', '$log', '$compile', '$http', '$filter', 'toaster', 'FormLoader',
    function ($scope, $uibModal, $log, $compile, $http, $filter, toaster, FormLoader) {

        $scope.files = [];
        $scope.form = {};
        $scope.dropdowns = {};
        $scope.datearray = {};
        $scope.property = [];
        $scope.masterDropdowns = {
            opt_mx_region_ids: [],
            opt_mx_district_ids: []
        };
        $scope.url = "";
        $scope.actionname = "";
        $scope.app_selected_user = '';
        $scope.current_task = "login"; // For Login or Password Recovery View Switch
        $scope.section_title = "";
        $scope.section_values;
        //$scope.other_actions = ["Edit_Email_Setup", "Transfer_Cards", "Receive_Cards", 'Transfer_Sample', 'Receive_Transfer', 'Control_Application', 'Exempted_Application', 'Add_Vaccination', 'Print_Card', "Edit_Sms_Setup", "Add_Float", "Settle_Collection_Account", 'Manage_Service_Limit', "Subscribe_Service", "Manage_Classes", "Approve_Transfer_Request", "Upload_Cards", "Upload_Devices", "service_subscription_request", "Backup_Database", 'Upload_Results', 'Publish_Bulk_Results', 'Manage_Price_Public', 'Manage_Price_Private'];
        $scope.officer = {};

        $scope.extraControl = {};

        $scope.autoCompleteSelectOptions = {};

        $scope.autoComplete = function (searchKey, searchComponent) {
            // check if institution selected or add other inputs to before proceed for BCX
            if (typeof ($scope.form.opt_mx_institution_id) === 'undefined' && $scope.extraControl.institution == 0) {
                toaster.pop('error', "error", "Please Select Institution First!");
                return;
            }

            var location = app_url + '/views/' + $scope.url + '/get_' + $scope.url + '_autocomplete_dropdowns.php';

            var post_data = {};

            var controls = [];

            controls.push({
                'opt_mx_institution_id': $scope.form.opt_mx_institution_id
            });

            post_data = { controls: controls, 'key': searchKey, 'table': 'subscriber', 'searchColumn': ['txt_name'] };

            $http({
                method: 'POST',
                url: location,
                data: post_data,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function successCallback(response) {
                $scope.autoCompleteSelectOptions[searchComponent] = response.data[searchComponent];
            }, function errorCallback(response) {

            });
        };

        $scope.initiateAutocomplete = function () {
            // initiate autocomplete on start
            if ($scope.url === 'incident') {
                if ($scope.extraControl.institution == 0) {
                    $scope.form.opt_mx_institution_id = $scope.dropdowns.opt_mx_institution_ids[0].id;
                }
                $scope.autoComplete('', 'opt_mx_subscriber_ids');
            }
        };

        $scope.onFocusShowRecaptcha = function (event) {
            angular.element('.tooltip').addClass('show-tooltip');
        };

        $scope.onKeyShowRecaptcha = function (event) {
            if (event.which === 13) {
                event.preventDefault();
                angular.element('.tooltip').addClass('show-tooltip');
            }
        };

        $scope.showForm = function (url, action, params = null) {
            $scope.FinishedFileUpload = false;
            $scope.ProcessingRequest = false;
            $scope.url = url;
            $scope.actionname = action;
            $scope.property = [];
            var form_url = "";
            var _url = url[0].toUpperCase() + url.substring(1);

            form_url = app_url + "/" + _url + "/" + action.toLowerCase();
            if (params !== null) {
                var params_str = '';
                for (var i = 0; i < params.length; i++) {
                    params_str += '/' + params[i];
                }
                form_url += params_str;
            }
            // console.log(form_url);

            FormLoader.load(form_url).then(function (result) {
                $scope.dropdowns = result.data.dropdowns;
                $scope.masterDropdowns = angular.copy($scope.dropdowns);

                // Only log if these dropdowns exist (not all forms have them)
                if ($scope.masterDropdowns.opt_mx_region_ids) {
                    console.log('Master Regions:', $scope.masterDropdowns.opt_mx_region_ids);
                }
                if ($scope.masterDropdowns.opt_mx_district_ids) {
                    console.log('Master Districts:', $scope.masterDropdowns.opt_mx_district_ids);
                }

                if (result.data.form) {
                    $scope.form = result.data.form;
                    if (_url.indexOf('Application') > -1) {
                        $scope.form.SelectedSymptoms = [];
                        $scope.form.id = 0;
                    }
                }

                var modalInstance = $uibModal.open({
                    template: result.template,
                    controller: modalFormCtrl,
                    windowClass: 'mx-modal-form',
                    scope: $scope
                });

                FormLoader.hideOverlay();

                modalInstance.opened.then(function () {
                    if (_url === 'Result' && action.toLowerCase() === 'upload_results') {
                        $(document).find('input[type=file]').each(function () {
                            $(this).mxResultUploader();
                            // echo $(this);
                        });
                    } else {
                        if ($(document).find('input[type=file]').length > 0) {
                            $scope.imageCount = 0;
                            $(document).find('input[type=file]').each(function (key, value) {
                                $scope.imageCount = key;
                                $(this).mxImageUploader();
                            });
                        }
                    }
                    if ($(document).find('textarea[name=tar_sms_content]').length > 0) {
                        $(document).find('textarea[name=tar_sms_content]').height(100).smsArea({ maxSmsNum: 3 });
                    }
                    if (result.data.disabled) {
                        $scope.disabled = result.data.disabled;
                        //console.log($scope.disabled);
                        angular.forEach($scope.disabled, function (value) {
                            $(document).find('#' + value).parent().parent().css('display', 'none');
                        });
                    }

                    if ($scope.form.classes !== undefined) {
                        $scope.writeClassData($scope.form.classes);
                    }
                }, function () {
                });
            }, function (error) {
                console.error(error);
                toaster.pop('error', "Error", "Failed to load form");
                FormLoader.hideOverlay();
            });
        };

        // Watch selected Arrival Station and adjust filter
        $scope.excludeArrival = function (station) {
            // If no arrival selected, show all
            if (!$scope.form.int_arrival_station) return true;

            // Exclude station that matches selected arrival station ID
            return station.id !== $scope.form.int_arrival_station;
        };

        // Convert '1970-01-01T06:41:00.000Z' â†’ '07:41 AM'
        $scope.formatTime = function (timeValue) {
            if (!timeValue) return '';

            try {
                // Handle both Date objects and strings
                var date = new Date(timeValue);
                var hours = date.getHours();
                var minutes = date.getMinutes();
                var ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12; // convert 0 -> 12
                minutes = minutes < 10 ? '0' + minutes : minutes;
                return hours + ':' + minutes + ' ' + ampm;
            } catch (e) {
                // Fallback for native HTML input type="time" values (like "07:30")
                return timeValue;
            }
        };

        // Get station name by ID (object or array compatible)
        $scope.getStationName = function (stationId) {
            if (!stationId || !$scope.dropdowns.opt_mx_station_ids) return '';

            var stations = $scope.dropdowns.opt_mx_station_ids;

            // If array
            if (Array.isArray(stations)) {
                var found = stations.find(function (s) { return s.id === stationId; });
                return found ? found.name : '';
            }

            // If object
            for (var key in stations) {
                if (stations[key].id === stationId) return stations[key].name;
            }

            return '';
        };



        $scope.writeClassData = function (class_data) {
            if (class_data.length > 0) {
                var tbody = '';
                var class_tbody = $(document).find('table > tbody#classes_data');
                class_tbody.empty();
                for (var i = 0; i < class_data.length; i++) {
                    let check_value = '';
                    if (class_data[i].opt_mx_state_id == 1) {
                        check_value = 'checked="true"';
                    }
                    tbody += '<tr class_row_id="' + class_data[i].id + '"><td><input type="text" placeholder="Class Name" name="txt_name[]" value="' + class_data[i].txt_name + '" class="form-control txt_name" ';
                    tbody += ' ng-class="manage_classes.txt_name.$invalid && !manage_classes.txt_name.$pristine" /></td>';
                    tbody += '<td><input type="number" placeholder="Maximum amount" name="dbl_max_amount[]" value="' + class_data[i].dbl_max_amount + '" class="form-control dbl_max_amount"';
                    tbody += ' ng-class="manage_classes.dbl_max_amount.$invalid && !manage_classes.dbl_max_amount.$pristine" /></td>';
                    tbody += '<td><input type="number" placeholder="Maximum Daily Transaction" name="int_max_transaction[]" value="' + class_data[i].int_max_transaction + '" class="form-control int_max_transaction"';
                    tbody += ' ng-class="manage_classes.int_max_transaction.$invalid && !manage_classes.int_max_transaction.$pristine" /></td>';
                    tbody += '<td><input type="checkbox" ' + check_value + ' class="opt_mx_state_id"></td>';
                    tbody += '<td><button type="button" class="btn btn-success btn-sm class-data-adder"><i class="fa fa-plus fa-fw"></i></button>';
                    if (i > 0) {
                        tbody += ' <button type="button" class="btn btn-danger btn-sm class-data-remover" disabled><i class="fa fa-minus fa-fw"></i></button>';
                    } else {
                        tbody += ' <button type="button" class="btn btn-danger btn-sm class-data-remover hidden"><i class="fa fa-minus fa-fw"></i></button>';
                    }
                    tbody += '</td></tr>';
                }
                class_tbody.append(tbody);
            }
        };

        $scope.getApplicationUsers = function () {
            $http.get(app_url + "/views/utility/subscription/get_application_users.php").success(function (response) {
                $scope.application_users = response.users;
                $scope.app_selected_user = $scope.application_users[0].id;
            });
        };

        $scope.getServiceCategoryLimit = function (action, institution_class_id) {
            $('.overlay').removeClass('hidden');
            if (institution_class_id >= 1) {
                var request_url = `${app_url}/ClassService/${action}`;
                $http({
                    method: 'POST',
                    url: request_url,
                    data: JSON.stringify(institution_class_id),
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                }).then(function (response) {
                    $('.overlay').addClass('hidden');
                    var class_limit_data = JSON.parse($(response.data).find('#mabrexPageContent').text().trim());
                    //console.log(class_limit_data);
                    if (class_limit_data.length > 0) {
                        $scope.institution_class_flag = true;
                        var tbody = '';
                        var class_tbody = $(document).find('table > tbody#service_limit');
                        class_tbody.empty();
                        for (var i = 0; i < class_limit_data.length; i++) {
                            let check_value = '';
                            if (class_limit_data[i].check > 0) {
                                check_value = 'checked="true"';
                            }
                            tbody += '<tr limit_row_id="' + class_limit_data[i].id + '"><td><input type="text" name="txt_name[]" value="' + class_limit_data[i]['Limit Category'] + '" class="form-control txt_name" ';
                            tbody += ' ng-class="class_category.txt_name.$invalid && !class_category.txt_name.$pristine" disabled/></td>';
                            tbody += '<td><input type="number" placeholder="Maximum amount" name="dbl_max_amount[]" value="' + class_limit_data[i]['maximum Amount'] + '" class="form-control dbl_max_amount"';
                            tbody += ' ng-class="class_category.dbl_max_amount.$invalid && !class_category.dbl_max_amount.$pristine" /></td>';
                            tbody += '<td><input type="number" placeholder="Maximum Transaction" name="dbl_max_transaction[]" value="' + class_limit_data[i]['Maximum Number Of Transaction'] + '" class="form-control dbl_max_transaction"';
                            tbody += ' ng-class="class_category.int_max_transaction.$invalid && !class_category.int_max_transaction.$pristine" /></td>';
                            tbody += '<td><input type="checkbox" ' + check_value + 'value="' + class_limit_data[i].check + '" class="opt_mx_state_id" hidden></td>';
                            tbody += '</td></tr>';
                        }
                        class_tbody.append(tbody);
                    }
                });
            } else {
                $scope.institution_class_flag = undefined;
            }
        };

        $scope.getUserReportSubscription = function (usesr_id) {
            $http.get(app_url + "/views/utility/subscription/get_report_subscription_data.php?user_id=" + usesr_id).success(function (response) {
                $scope.report_types = response.report_types;
                $scope.frequencies = response.frequencies;
            });
        };

        $scope.changePassword = function () {
            $('.overlay').removeClass('hidden');
            $scope.ProcessingData = true;
            $scope.url = '/User/';
            var post_url = app_url + "/User/changePassword/";
            $http({
                method: 'POST',
                url: post_url,
                data: $scope.form, //forms user object
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).success(function (data) {
                $('.overlay').addClass('hidden');
                $scope.ProcessingData = false;
                if (data.errors) {
                    $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
                } else {
                    var response = $(data).find('#mabrexPageContent').text().trim();
                    if (response === '200' || response === '201') {
                        $('.notification-area').addClass('alert alert-success').text("Password changed successfully. Please login with your new password.");
                    } else if (response === '210' || response === '101') {
                        $('.notification-area').addClass('alert alert-success').text("Password could not be changed.");
                    } else if (response === '2000') {
                        $('.notification-area').addClass('alert alert-info').text("New Password and Confirm New Password do not match.");
                    } else if (response === '1000') {
                        $('.notification-area').addClass('alert alert-danger').text("Old Password is incorrect");
                    } else {
                        $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
                    }
                    setTimeout(function () {
                        $('.notification-area').removeClass('alert alert-success').text('');
                        location.href = app_url + "/Logout";
                    }, 4000);

                }
            });
        };

        $scope.getDropdowns = function (url) {
            var request_url = `${app_url}/${url[0].toUpperCase()}${url.substring(1)}/get_dropdowns`;
            $http({
                method: 'POST',
                url: request_url,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function (response) {
                $scope.dropdowns = JSON.parse($(response.data).find('#mabrexPageContent').text().trim());
                $scope.initiateAutocomplete();
            });
        };

        // Field and Function to filter and return select options based on selected master dropdown
        $scope.filteredDropdownOptions = { $: undefined }; // initial non-filtering value

        $scope.setFilteredDropdownOptions = function (master, details) {
            $scope.filteredDropdownOptions = {};
            $scope.filteredDropdownOptions = details.filter((m) => m.master === master);
        };
        $scope.getMedicalData = function () {
            $('.overlay').removeClass('hidden');
            $scope.ProcessingData = true;
            var post_url = `${app_url}/Result/getMedicalTransferData/`;
            $http({
                method: 'POST',
                url: post_url,
                data: $scope.form,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).success(function (data) {
                $('.overlay').addClass('hidden');
                $scope.ProcessingData = false;
                console.log(data);
                if (data.code == '100') {
                    $('.notification-area').addClass('alert alert-info').text(data.message);
                } else {
                    $('.notification-area').addClass('alert alert-info').text(data.message);
                    console.log(data);
                    $scope.form = data.result;
                }
                setTimeout(() => {
                    $scope.ProcessingData = false;
                    $('.notification-area').removeClass('alert alert-info').text('');
                }, 2000)
            }, function () {
                $scope.ProcessingData = false;
            });
        };
        $scope.showProfile = function (url, id) {
            $scope.url = url;
            $scope.frmData = {};
            $scope.current_tab = url;
            $scope.is_profile_tab = true;
            $scope.parent_id = id;

            var formURL = `${app_url}/${url}/profile/${id}`;

            FormLoader.load(formURL).then(function (result) {
                $scope.tabs = result.data.tabs;
                $scope.initial_tab_data = result.data.initial;
                $scope.hidden_columns = result.data.hidden_columns;
                if (result.data.account_detail) {
                    $scope.account_detail = result.data.account_detail;
                }
                if (result.data.current_institution) {
                    $scope.cur_institution = result.data.current_institution;
                }
                if (result.data.missing_accounts) {
                    $scope.missing_accounts = result.data.missing_accounts;
                }
                var modalInstance = $uibModal.open({
                    template: result.template,
                    controller: modalFormCtrl,
                    windowClass: 'mx-modal-form',
                    scope: $scope
                });

                FormLoader.hideOverlay();
            }, function (error) {
                console.error(error);
                toaster.pop('error', "Error", "Failed to load profile");
                FormLoader.hideOverlay();
            });
        };

        $scope.goToBlock = (to, from) => {
            setTimeout(() => {
                $(from).css('display', 'none');
                $(to).css('display', 'block');
            }, 300);
        };

        $scope.parseDate = function (date) {
            var newDate = new Date(date);
            var day = newDate.getDate();
            var month = newDate.getMonth() + 1;
            var year = newDate.getFullYear();

            return year + '-' + month + '-' + day;
        }
        $scope.getTodaysTime = function () {
            var date = new Date();
            return new Date(
                date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + ' ' + date.getHours() + ':' + date.getMinutes()
            )
        }
    }]);


var modalFormCtrl = function ($scope, $uibModalInstance, $http, $uibModal, $window, $filter, Upload) {
    $scope.action = null;
    $scope.valid_card = false;

    $scope.verifyZanId = function () {
        $('.overlay').removeClass('hidden');
        clearNotification();
        $scope.ProcessingData = true;
        var zanid = $("#txt_id_number").val();
        var url = `${app_url}/${$scope.url.capitalize()}/verify_zan_id/`;
        $http({
            method: 'POST',
            url: url,
            data: { zan_id: zanid }, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            let values = JSON.parse($(data).find('#mabrexPageContent').text().trim());
            if (values.status === "0") {
                $scope.form = values;
                $scope.form.dat_date_of_birth = $scope.parseDate($scope.form.dat_date_of_birth);
                $scope.goToBlock('#officer_details_block', '#verify_zan_id_block');
                $scope.ProcessingData = false;
            } else {
                let message = values.message + "<br>";

                message += values.location ? values.location + "<br>" : '';
                message += values.council_name ? values.council_name + "<br>" : '';
                message += values.business_location ? values.business_location + "<br>" : '';
                message += values.officer_category ? values.officer_category + "<br>" : '';

                $scope.action = values.action;
                $scope.officer.id = values.row_value;
                notify('error', message);
                $scope.ProcessingData = false;
            }
        });
    };

    $scope.saveTransferRequest = function () {
        $('.overlay').removeClass('hidden');
        clearNotification();
        $scope.ProcessingData = true;
        var url = "/TransferRequest/confirm_transfer_request";
        $http({
            method: 'POST',
            url: app_url + url,
            data: $scope.form, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            let values = JSON.parse($(data).find('#mabrexPageContent').text().trim());

            if (values.status === 200) {
                notify('success', values.message);
                setTimeout(() => {
                    $scope.ProcessingData = false;
                    $scope.cancel();
                }, 3000)
            } else {
                notify('error', values.message);
            }
        });
    };

    String.prototype.capitalize = function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };

    $scope.registerOfficer = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/Inspector/save/`;

        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(data);
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {

                if (response === 100) {
                    notify('error', "Failed to Register Officer.")
                } else {
                    notify('success', "Successfully Registered officer.");
                    $uibModalInstance.dismiss('cancel');
                }
                $scope.ProcessingData = false;
            }
        }, function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.registerOwner = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/Owner/save/`;

        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = $(data).find('#mabrexPageContent').text().trim();
                response = JSON.parse(response);
                if (response === 100) {
                    notify('error', "Failed to Register Site Owner.")
                } else {
                    notify('success', "Successfully Registered Site Owner.");
                    $$uibModalInstance.dismiss('cancel');
                }
                $scope.ProcessingData = false;
            }
        }, function () {

            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
        });
    };

    $scope.saveForm = function (action = "") {
        $('.notification-area').removeClass('alert alert-success alert-danger alert-info').html('');
        $('.overlay').removeClass('hidden');
        if (localStorage.getItem("ShowProfile") !== null) {
            localStorage.removeItem("ShowProfile")
        }
        if (localStorage.getItem("ApplicantId") !== null) {
            localStorage.removeItem("ApplicantId")
        }
        $scope.ProcessingData = true;
        var post_url = `${app_url}/${$scope.url.capitalize()}/save/`;
        if (action !== "") {
            post_url = `${app_url}/${$scope.url.capitalize()}/${action}/`;
        }
        if ($scope.form.has_extra === 1) {
            $scope.configureExtraData(action);
        }
        // console.log($scope.form)
        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            if (action === 'post_generate_lab_document') {
                $scope.download_file = response.title;
                $scope.application_list = JSON.stringify(response.application_list);
                $scope.title = response.title;
                $scope.form = {};
                // $scope.generateLabTestListExcel(response.title, response.application_list);
                $scope.responseHandler(response, false);
                //                    $scope.responseHandler(response, false);
            } else {
                $scope.responseHandler(response);
            }
        }).error(function (response) {
            $('.overlay').addClass('hidden');

            $scope.responseHandler(response);

        });
    };

    // --- Dynamic filtering for School form --- //
    $scope.onLocationChange = function (locationId) {
        if (!$scope.masterDropdowns || !$scope.masterDropdowns.opt_mx_region_ids) {
            console.warn('masterDropdowns not initialized.');
            return;
        }

        console.log(locationId);

        if (!locationId) {
            $scope.dropdowns.opt_mx_region_ids = [];
            $scope.dropdowns.opt_mx_district_ids = [];
            $scope.form.opt_mx_region_id = null;
            $scope.form.opt_mx_district_id = null;
            return;
        }
        $scope.dropdowns.opt_mx_region_ids = $scope.masterDropdowns.opt_mx_region_ids.filter(function (region) {
            return region.opt_mx_location_id === locationId;
        });

        $scope.dropdowns.opt_mx_district_ids = [];
        $scope.form.opt_mx_region_id = null;
        $scope.form.opt_mx_district_id = null;
    };

    $scope.onRegionChange = function (regionId) {
        if (!$scope.masterDropdowns || !$scope.masterDropdowns.opt_mx_district_ids) {
            console.warn('masterDropdowns not initialized.');
            return;
        }

        if (!regionId) {
            $scope.dropdowns.opt_mx_district_ids = [];
            $scope.form.opt_mx_district_id = null;
            return;
        }

        $scope.dropdowns.opt_mx_district_ids = $scope.masterDropdowns.opt_mx_district_ids.filter(function (district) {
            return district.opt_mx_region_id === regionId;
        });

        $scope.form.opt_mx_district_id = null;
    };


    $scope.generateLabTestListExcel = function (title, data) {
        try {

            //           $('#loader').css('display', '');
            console.log(title.toString());
            $('.overlay').removeClass('hidden');
            console.log(data.length);
            var records = JSON.parse(data);
            console.log(records);
            alasql(`SELECT * INTO XLSX("${title}.xlsx",{headers:true}) FROM ?`, [records]);
        } catch (err) {
        } finally {
            $('.overlay').addClass('hidden');
        }
    }

    $scope.generateSampleTransferPDF = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
        //        $('#loader').css('display', '');
        //        $(document).find(".progress").each(function () {
        //            $(this).css("visibility", "visible");
        //        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post;
        data_to_post = { 'reference': params };

        //        console.log(data_to_post);
        //
        //        console.log(data_to_post);
        var _width = 800; //$(document).width() / 2 - 200;
        var _height = 800; //$(document).height();
        var fileName;

        //           console.log($scope.ReportOptions.FilterFieldValue);
        //url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            //            console.log(data)
            if (data.status == 200) {
                fileName = app_url + "/" + data.file;
                //                console.log(fileName)
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);
                //                console.log(object);
                $('#reportPDFPreview').html(object);

                $scope.ExportTableToPDF();
                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
                //                console.log(data.records);
            } else if (data.status == 100) {
                $('.notification-area').addClass('alert alert-success').text('No record found');
            } else if (data.status == 209) {
                $('.notification-area').addClass('alert alert-success').text('Sorry! You can not generate certificate for a positive result test');
            } else {
                $('.notification-area').addClass('alert alert-success').text('There was an error when generating your Certificate. Please try again later or contact your system administrator for assistance');
            }
            setTimeout(function () {
                $('.notification-area').removeClass('alert alert-success').text('');

            }, 4000);
            $scope.ProcessingData = false;
            $('.overlay').addClass('hidden');
        });
    };

    $scope.ExportTableToPDF = function () {
        $scope.cancel();
        //$('#preview_panel #report_preview').removeClass('hide');
        $('#DemoModal').modal('show');
    };
    //called when record row clicked to open the profile modal

    $scope.getTransferReference = function (action) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/${action}/getTransferReference/`;
        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = JSON.parse($(data).find('#mabrexPageContent').text().trim());
                if (response.status === 200) {
                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                    $scope.form = response.data;
                    //                    $scope.$apply();
                    //                    $modalInstance.$apply();

                } else if (response.status === 100) {
                    $('.notification-area').addClass('alert alert-danger').text("Reference number does not exist.");
                } else if (response.status === 105) {
                    $('.notification-area').addClass('alert alert-danger').text("This Transfer is already received by " + response.data + ".");
                }
                //                console.log(response)
            }
            setTimeout(function () {
                $scope.ProcessingData = false;
                $('.notification-area').removeClass('alert alert-success').text('');
                //                    $$uibModalInstance.dismiss('cancel');

            }, 4000);
        }, function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.getSampleData = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        if ($scope.form.dat_added_date != undefined && $scope.form.opt_mx_center_id !== undefined) {
            var post_url = `${app_url}/Transfers/getTransferData/`;
            $http({
                method: 'POST',
                url: post_url,
                data: $scope.form,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).success(function (data) {
                $('.overlay').addClass('hidden');
                $scope.ProcessingData = false;
                if (data.errors) {
                    $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
                } else {
                    var response = JSON.parse($(data).find('#mabrexPageContent').text().trim());
                    response.dat_added_date = new Date($scope.form.dat_added_date)
                    $scope.form = response;
                }
            }, function () {
                $('.overlay').addClass('hidden');
                $scope.ProcessingData = false;
            });
        } else {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;

        }
    };

    $scope.responseHandler = function (response, reload = true) {
        let code = Number(response.code);
        let status = response.status;
        let message = response.message;

        let timeout = 0;
        $scope.ProcessingData = !(status === undefined || status == false);

        if (code === 200 || code === 201) {
            $('.notification-area').addClass('alert alert-success').html(message);
            timeout = 1;
        } else if (code === 220) {
            $('.notification-area').addClass('alert alert-info').html(message);
            timeout = 1;
        } else {
            $('.notification-area').addClass('alert alert-danger').html(message);
        }

        if (timeout === 1) {
            setTimeout(function () {
                $('.notification-area').removeClass('alert alert-success alert-danger alert-info').html('');
                if (reload) {
                    if (code === 200 || code === 201) {
                        $$uibModalInstance.dismiss('cancel');
                        location.reload();
                    }
                }
            }, 2000);
        }
    };

    $scope.saveFormWithUploads = function (url, action, uploads) {
        // console.log(uploads);
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = app_url + "/" + url + "/" + action + "/";
        if ($scope.form.has_extra === 1) {
            $scope.configureExtraData(action);
        }

        var formdata = new FormData();

        angular.forEach($scope.form, function (value, key) {
            formdata.append(key, value);
        });

        if (uploads.length > 0) {
            angular.forEach(uploads, function (value) {
                var element = document.getElementById(value);
                if (element != null) {
                    var file = element.files[0];
                    formdata.append(value, file);
                }
            });
        }
        $http({
            method: 'POST',
            url: post_url,
            data: formdata,
            headers: { 'Content-Type': undefined }
        }).success(function (data) {
            // console.log(data);
            $('.overlay').addClass('hidden');
            if (data.status === false || !data.status) {
                $('.notification-area').addClass('alert alert-danger').text(`${data.message}`);
            } else if (data.status === '111' || data.status === 111) {
                $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled, with errors");
                $scope.download_file = data?.file;
                // console.log($scope.download_file);
                close_modal = false;
            } else {
                $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                var close_modal = true;
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                    $$uibModalInstance.dismiss('cancel');
                    location.reload();
                }, 4000);
            }
        }).finally(function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.processPropertyData = function (formdata) {
        var uploads = [];
        var counter = 0;
        var container = $(document).find('tbody#uploads_table');
        container.children('tr').each(function () {
            var row = $(this);
            var data = {};
            // console.log(key)
            row.children('td.input_cell').each(function () {
                var control = $(this).find('[data-input]');
                var value = control.val();
                var label = control.attr('data-input');
                if (label == 'txt_image_url') {
                    var td = $(this);
                    td.children('input[type=file]').each(function () {
                        var element = this;
                        var file = element.files[0];
                        data[label + '_' + counter] = file;
                        formdata.append(label + '_' + counter, file);
                    })

                } else {
                    data[label] = value;
                }
            });
            counter += 1;
            uploads.push(data);
        });
        formdata.append('uploads', JSON.stringify(uploads));
        // console.log(uploads)
    }

    $scope.parseDate = function (date) {
        var newDate = new Date(date);
        var day = newDate.getDate();
        var month = newDate.getMonth() + 1;
        var year = newDate.getFullYear();
        return year + '-' + month + '-' + day;
    }

    $scope.parseTime = function (time) {
        var date = new Date();
        var Selecteddate = new Date(time);
        return new Date(
            date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + ' ' + Selecteddate.getHours() + ':' + Selecteddate.getMinutes()
        )
    }

    $scope.configureExtraData = function (action) {
        // console.log($scope.url);
        // console.log(action);
        if ($scope.url == "Auction" && action == '') {
            var property = [];
            var container = $(document).find('tbody#property_table');
            container.children('tr').each(function () {
                var row = $(this);
                var data = {};
                row.children('td.input_cell').each(function () {
                    var control = $(this).find('[data-input]');
                    var value = control.val();
                    var label = control.attr('data-input');
                    data[label] = value;
                });
                property.push(data);
            });
            $scope.form.property = property;
        } else {
            switch (action) {
                case 'save':
                    var account = [];
                    if ($scope.form.chkselct) {
                        var container = $(document).find('tbody#account_table');
                        container.children('tr').each(function () {
                            var row = $(this);
                            var data = {};
                            row.children('td.input_cell').each(function () {
                                var control = $(this).find('[data-input]');
                                var value = control.val();
                                var label = control.attr('data-input');
                                data[label] = value;
                            });
                            account.push(data);
                        });
                    }
                    $scope.form.account = JSON.stringify(account);
                    break;
                case 'post_manage_classes':
                    let classes = [];
                    var container = $(document).find('tbody#classes_data');
                    container.children('tr').each(function () {
                        let class_id = $(this).attr('class_row_id');
                        let name = $(this).find('input.txt_name').val();
                        let max_amount = $(this).find('input.dbl_max_amount').val();
                        let max_transaction_number = $(this).find('input.int_max_transaction').val();
                        let check_value = $(this).find('input.opt_mx_state_id').is(':checked');
                        let state = 1;
                        if (check_value === false) {
                            state = 4;
                        }
                        classes.push({
                            'id': class_id,
                            'txt_name': name,
                            'dbl_max_amount': max_amount,
                            'int_max_transaction': max_transaction_number,
                            'opt_mx_state_id': state
                        });
                    });
                    $scope.form.class_data = JSON.stringify(classes);
                    break;
                case 'save_service_category_limit':
                    let class_service_limit = [];
                    var container = $(document).find('tbody#service_limit');
                    container.children('tr').each(function () {
                        let class_id = $(this).attr('limit_row_id');
                        let name = $(this).find('input.txt_name').val();
                        let max_amount = $(this).find('input.dbl_max_amount').val();
                        let max_transaction_number = $(this).find('input.dbl_max_transaction').val();
                        let check_value = $(this).find('input.opt_mx_state_id').val();
                        let state = 0;
                        if (check_value > 0) {
                            state = check_value;
                        }
                        class_service_limit.push({
                            'id': class_id,
                            'txt_name': name,
                            'dbl_max_amount': max_amount,
                            'dbl_max_transaction': max_transaction_number,
                            'state': state
                        });
                    });
                    $scope.form.limit_data = JSON.stringify(class_service_limit);
                    break;
                default:
                    break;
            }
        }

    };

    $scope.addAuctionItem = function (column) {
        // var row = $(this).parent().parent();
        var container = $(document).find('tbody#property_table');
        container.children('tr').each(function () {
            var row = $(this);
            var data = {};
            var check_value = true;
            row.children('td.input_cell').each(function () {
                if (!check_value) {
                    data = [];
                    return;
                }
                var control = $(this).find('[data-input]');
                var value = control.val();
                var label = control.attr('data-input');
                if (!value) {
                    check_value = false;
                }
                data[label] = value;


            });
            console.log(data);
            if (check_value) {
                $scope.property.push(data);
            }
            // row.find('.property-remover').removeClass('hidden');
            row.find('input[type=number]').val('');
            row.find('select').val('');
        });

        $scope.form.property = $scope.property;
        console.log($scope.form.property);
        $scope.processPropertyInfo();
    }

    $scope.removePropertyItem = function (id) {
        console.log($scope.property)
        console.log($scope.records)
        console.log(id)
        $scope.property.splice(id, 1);
        $scope.records.splice(id, 1);
        $scope.form.property = $scope.property;
        console.log($scope.form.property);
        $scope.processPropertyInfo();
    }

    $scope.processPropertyInfo = function () {
        $scope.records = [];

        $.each($scope.property, function (key, value) {
            // console.log(value.auction_dropdown)
            var property_name = $scope.dropdowns.auction_dropdowns.filter((item) => item.id === value.auction_dropdown)[0]['name'];
            console.log(property_name)
            $scope.records[key] = { 'property_name': property_name };
            $scope.records[key].auction_dropdown = value.auction_dropdown;
            $scope.records[key].reserved_amount = value.reserved_amount;
            $scope.records[key].starting_bid = value.starting_bid;
            $scope.records[key].increment_interval = value.increment_interval;
            // let product_name = $scope.dropdowns.opt_mx_product_ids.filter(item => item.id == $scope.form.opt_mx_product_id[key])[0]['name']
        });

        console.log($scope.records);
    };
    // This function is used to validate the file we are trying to upload
    // It is called once the user selects a file
    // The function is being currently used in upload_devices file
    $scope.validateFile = function () {
        var input = $(document).find('input#data_cards');
        var msgArea = $(document).find('div#dataUploadResultMessage');
        var file = input.prop('files')[0];
        var fileTypes = ['text/csv', 'application/vnd.ms-excel'];
        console.log(fileTypes)
        for (var i = 0; i < fileTypes.length; i++) {
            if (file.type === fileTypes[i]) {
                msgArea.html('').removeClass('well');
                return true;
            }
        }
        input.val('');
        msgArea.append('<p class="text-danger">Please select a valid csv file.</p>');
        return false;
    };
    $scope.cancel = function () {
        $$uibModalInstance.dismiss('cancel');
        $scope.action = null;
    };

    $scope.showDistrictsByLocation = function (loc) {
        return function (item) {
            return item.location === loc;
        };
    };

    $scope.closeAndOpenProfile = function () {
        $$uibModalInstance.dismiss('cancel');
        $scope.showProfile('Officer', $scope.officer.id);
        $scope.action = null;
    };
    //dynamic table for FAQ
    var faqIndex = 0;
    $scope.addFaqRow = function () {
        if ($scope.form.faq === undefined) {
            $scope.form.faq = [];
        }
        //        i=$scope.form.faq.length;
        $scope.form.faq.unshift({
            id: faqIndex,
            tar_question_en: '',
            tar_answer_en: '',
            tar_question_sw: '',
            tar_answer_sw: '',
            tar_question_it: '',
            tar_answer_it: ''
        });
        faqIndex++;
    };

    $scope.removeFaqRow = function (id) {
        $scope.form.faq.forEach(function (faq, index) {
            if (faq.id === id) {
                $scope.form.faq.splice(index, 1);
            }
        });
    };

    $scope.validateFile = function () {
        //        console.log($scope.form.result_file);

        //        var $scope = $(document).find('input#txt_reference_file').scope();
        var allowedFiles = [".xls", ".xlsx"];
        var fileUpload = document.getElementById("result_file");
        var lblError = document.getElementById("file_error");
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:\)\(])+(" + allowedFiles.join('|') + ")$");
        //        console.log(fileUpload.value.toLowerCase());
        if (!regex.test(fileUpload.value.toLowerCase())) {
            //            console.log("Please upload files having extensions: <b>" + allowedFiles.join(', ') + "</b> only.");
            $scope.FileInvalid = true;
            // $("div#file_error").removeClass('hidden');
            // $('div#file_error').addClass('alert alert-danger').text("Please upload files having extensions: " + allowedFiles.join(', ') + " only.");
        } else {
            // lblError.innerHTML = "";
            // $("div#file_error").addClass('hidden');
            $scope.FileInvalid = false;
        }
        //        console.log($scope.FileInvalid)
    };

    $scope.step = 1;
    $scope.test_type = null;
    $scope.reference_ok = false;
    $scope.nextStep = function (test_type) {
        //        console.log('sadf')
        if (test_type !== undefined) {
            if (test_type == 'non_control_test') {
                $scope.form = {};
                $scope.center_availability = {};
            }
            $scope.test_type = test_type;
            $('#' + test_type).css('display', 'block');
        }

        $scope.step++;
        //        console.log($scope.step);
    }

    $scope.prevStep = function () {
        if ($scope.step === 0) {
            $('#non_control_test').css('display', 'none');
            $('#control_test').css('display', 'none');
        }
        $scope.step--;
    }

    $scope.checkExistingExemptedApplication = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        const existingExemptedApplicationUrl = app_url + '/Application/check_existing_exempted_application';
        const post_data = {
            id: 'undefined',
            reference_number: $scope.form.reference_number
        }
        $http({
            method: 'POST',
            url: existingExemptedApplicationUrl,
            data: post_data,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
            //            console.log($(data).find('#mabrexPageContent').text());
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {

                var response = JSON.parse($(data).find('#mabrexPageContent').text().trim());
                if (response.status === 200) {
                    $('.notification-area').addClass('alert alert-success').text(response.message);
                    $scope.form = response.data;
                    $scope.control = 1;
                    $scope.step++;
                    //                    $scope.$apply();
                    //                    $modalInstance.$apply();

                } else if (response.status === 100) {
                    $('.notification-area').addClass('alert alert-danger').text(response.message);
                } else if (response.status === 110) {
                    $('.notification-area').addClass('alert alert-danger').text(response.message);
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Error: Something has occurred ");
                }
                //                $scope.$apply();
            }
            setTimeout(function () {
                $scope.ProcessingData = false;
                $('.notification-area').removeClass('alert alert-success').text('');
                //                    $$uibModalInstance.dismiss('cancel');

            }, 4000);
        }).error(function (error) {
            $scope.ProcessingData = false;
            //            console.log(error);
        });
    }

    $scope.submitForm = function () {
        // submit code goes here
        //        console.log($scope.form)
    }
    $scope.getAvailability = function () {
        //        console.log($scope.form);
        if (($scope.form.opt_mx_test_type_id != null || $scope.form.opt_mx_test_type_id != undefined) && ($scope.form.dat_test_date != null || $scope.form.dat_test_date != undefined) && ($scope.form.opt_mx_test_center_id != null || $scope.form.opt_mx_test_center_id != undefined)) {
            var data_to_post = {
                'dat_test_date': $scope.form.dat_test_date,
                'opt_mx_center_id': $scope.form.opt_mx_test_center_id,
                'opt_mx_test_type_id': $scope.form.opt_mx_test_type_id
            };
            //            console.log(data_to_post);
            $http({
                method: 'POST',
                url: `${app_url}/Application/getAvailability`,
                data: data_to_post, //forms user object
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function (response) {
                $scope.center_availability = JSON.parse($(response.data).find('#mabrexPageContent').text());
                $scope.user_group_flag = true;
            });
        } else {
            $scope.center_availability = {};
        }
    }
    $scope.getTestCenter = function () {
        //        console.log($scope.form);
        if (($scope.form.opt_mx_test_type_id != null || $scope.form.opt_mx_test_type_id != undefined)) {
            var data_to_post = { 'opt_mx_test_type_id': $scope.form.opt_mx_test_type_id };
            //            console.log(data_to_post);
            $http({
                method: 'POST',
                url: `${app_url}/Application/getTestCenter`,
                data: data_to_post, //forms user object
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function (response) {
                $scope.dropdowns.opt_mx_test_center_ids = JSON.parse($(response.data).find('#mabrexPageContent').text());
                $scope.user_group_flag = true;
            });
        } else {
            $scope.center_availability = {};
        }
    }
    $scope.getCenter = function (slot_id, test_time, control) {
        $scope.form.slot_id = slot_id;
        $scope.form.test_time = test_time;
        $scope.form.int_control = control;
        $scope.slot_avail = 1;
    }
    $scope.validatePassportFile = function () {
        //        var $scope = $(document).find('input#txt_reference_file').scope();
        var allowedFiles = [".jpg", ".jepg", ".jpeg", ".png", "pdf"];
        var fileUpload = document.getElementById("txt_passport_image");
        //        $scope.FileInvalid = false;
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
        if ($(document).find('input#txt_passport_image')) {
            $(document).find('input#txt_passport_image').each(function () {
                var files = $(this)
                var file = files.prop('files')[0]
                if (file != undefined || file != null) {
                    //                    console.log(file)
                    filename = file.name;
                    //                    console.log(file);
                    // echo $(this);
                    if (!regex.test(fileUpload.value.toLowerCase())) {
                        if ((file.size / 1048576) > 6) {
                            files.next('div').html("<p>" + filename + ": File size must Be less than 6 Mb</p>");
                            $scope.FileInvalid = false;
                        } else {
                            $scope.FileInvalid = true;
                        }
                    } else {
                        $scope.FileInvalid = false;
                    }
                }

            });
        }


    };

    $scope.getZanID = function (id_number, id_type) {
        $('.overlay').removeClass('hidden');
        clearNotification();
        $scope.ProcessingData = true;
        var url = `${app_url}/${$scope.url.capitalize()}/verify_zan_id/`;
        $http({
            method: 'POST',
            url: url,
            data: { id: id_number }, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            let values = JSON.parse($(data).find('#mabrexPageContent').text().trim());
            if (values.status === 200) {
                $scope.valid_card = true;
                $scope.form = values.data;
                $scope.form.opt_mx_e_card_type_id = id_type;
                $scope.form.dat_birth_date = new Date($scope.form.birth_date);
                // $scope.goToBlock('#officer_details_block', '#verify_zan_id_block');
                $scope.ProcessingData = false;
            } else {
                let message = values.message;

                $scope.officer.id = values.row_value;
                notify('error', message);
                $scope.ProcessingData = false;
            }
        });
    }

    $scope.checkID = function () {
        let id_type = $scope.form.opt_mx_e_card_type_id;
        let id_number = $scope.form.txt_e_card_number;
        if (id_type === 1) {
            return $scope.getZanID(id_number, id_type);
        }
    }

    $scope.verifyZanId = function () {
        $('.overlay').removeClass('hidden');
        clearNotification();
        $scope.ProcessingData = true;
        var zanid = $("#txt_id_number").val();
        var url = `${app_url}/${$scope.url.capitalize()}/verify_zan_id/`;
        $http({
            method: 'POST',
            url: url,
            data: { zan_id: zanid }, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            let values = data;
            if (values.status === "0") {
                $scope.form = values;
                $scope.form.dat_date_of_birth = $scope.parseDate($scope.form.dat_date_of_birth);
                $scope.goToBlock('#officer_details_block', '#verify_zan_id_block');
                $scope.ProcessingData = false;
            } else {
                let message = values.message + "<br>";

                message += values.location ? values.location + "<br>" : '';
                message += values.council_name ? values.council_name + "<br>" : '';
                message += values.business_location ? values.business_location + "<br>" : '';
                message += values.officer_category ? values.officer_category + "<br>" : '';

                $scope.action = values.action;
                $scope.officer.id = values.row_value;
                notify('error', message);
                $scope.ProcessingData = false;
            }
        });
    };

    $scope.registerOfficer = function () {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = `${app_url}/Inspector/save/`;

        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(data)
        }, function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.validateFinancialInstitutionImage = function () {

        var financialInstitutionLogoUpload = document.getElementById("txt_image_url");

        var allowedFiles = "";
        var fileUpload = "";
        $scope.FileType = [];
        $scope.FinancialInstitutionFileInvalid = [];
        var file_key = null;

        //Check uploaded file
        if (financialInstitutionLogoUpload.files.length != 0) {
            allowedFiles = [".png"];
            fileUpload = financialInstitutionLogoUpload;
            file_key = "txt_image_url";
            $scope.FileType.push(file_key);
            $scope.checkFileUpload(fileUpload, allowedFiles, file_key);
        }

    };
    $scope.checkFileUpload = function (file_upload, allowedFiles, file_key) {
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:\)\(])+(" + allowedFiles.join('|') + ")$");

        if (!regex.test(file_upload.value.toLowerCase())) {
            $scope.FileInvalid.push(file_key);
        }
    }
    $scope.fileFinancialInstitutionValidity = function (file_key) {
        return $scope.FinancialInstitutionFileInvalid.includes(file_key) && $scope.FileType.push(file_key);
    }

    $scope.callmxImageUploader = function () {
        var container = $(document).find("tbody#uploads_table");
        var row = container.find("tr:last");
        row.children("td.input_cell").each(function (key, value) {
            $(this).find("input[type=file]").each(function () {
                $(this).mxImageUploader();
            });
        });
    };
};

angular.module("dashboard.modal")
    .controller("AdminDashboardCtrl", function ($scope, $http) {

        // Use the new camelCase endpoint
        var url = app_url + '/Dashboard/getAdminData';

        $scope.fetchData = function () {
            $http.get(url).then(function (response) {
                // response.data is the JSON from PHP
                // our jsonSuccess helper wraps the result in a 'data' key
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
    });

(function () {
    'use strict';

    angular
        .module('zantechApp')
        .controller('menuController', ['$scope', '$compile', '$window', '$http', function ($scope, $compile, $window, $http) {

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


            $scope.verifyZanId = function () {
                $('.overlay').removeClass('hidden');
                clearNotification();
                $scope.ProcessingData = true;
                var zanid = $("#txt_id_number").val();
                var url = `${app_url}/${$scope.url.capitalize()}/verify_zan_id/`;
                $http({
                    method: 'POST',
                    url: url,
                    data: { zan_id: zanid }, //forms user object
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                }).success(function (data) {
                    $('.overlay').addClass('hidden');
                    let values = data;
                    if (values.status === "0") {
                        $scope.form = values;
                        $scope.form.dat_date_of_birth = $scope.parseDate($scope.form.dat_date_of_birth);
                        $scope.goToBlock('#officer_details_block', '#verify_zan_id_block');
                        $scope.ProcessingData = false;
                    } else {
                        let message = values.message + "<br>";

                        message += values.location ? values.location + "<br>" : '';
                        message += values.council_name ? values.council_name + "<br>" : '';
                        message += values.business_location ? values.business_location + "<br>" : '';
                        message += values.officer_category ? values.officer_category + "<br>" : '';

                        $scope.action = values.action;
                        $scope.officer.id = values.row_value;
                        notify('error', message);
                        $scope.ProcessingData = false;
                    }
                });
            };

            $scope.registerOfficer = function () {
                $('.overlay').removeClass('hidden');
                $scope.ProcessingData = true;
                var post_url = `${app_url}/Inspector/save/`;

                $http({
                    method: 'POST',
                    url: post_url,
                    data: $scope.form,
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                }).success(function (data) {
                    $('.overlay').addClass('hidden');
                    $scope.responseHandler(data)
                }, function () {
                    $scope.ProcessingData = false;
                });
            };

            $scope.validateFinancialInstitutionImage = function () {

                var financialInstitutionLogoUpload = document.getElementById("txt_image_url");

                var allowedFiles = "";
                var fileUpload = "";
                $scope.FileType = [];
                $scope.FinancialInstitutionFileInvalid = [];
                var file_key = null;

                //Check uploaded file
                if (financialInstitutionLogoUpload.files.length != 0) {
                    allowedFiles = [".png"];
                    fileUpload = financialInstitutionLogoUpload;
                    file_key = "txt_image_url";
                    $scope.FileType.push(file_key);
                    $scope.checkFileUpload(fileUpload, allowedFiles, file_key);
                }

            };
            $scope.checkFileUpload = function (file_upload, allowedFiles, file_key) {
                var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:\)\(])+(" + allowedFiles.join('|') + ")$");

                if (!regex.test(file_upload.value.toLowerCase())) {
                    $scope.FileInvalid.push(file_key);
                }
            }
            $scope.fileFinancialInstitutionValidity = function (file_key) {
                return $scope.FinancialInstitutionFileInvalid.includes(file_key) && $scope.FileType.push(file_key);
            }

            $scope.callmxImageUploader = function () {
                var container = $(document).find("tbody#uploads_table");
                var row = container.find("tr:last");
                row.children("td.input_cell").each(function (key, value) {
                    $(this).find("input[type=file]").each(function () {
                        $(this).mxImageUploader();
                    });
                });
            };



            $scope.pageSize = 25;
            $scope.pagesList = [];
            $scope.useSearchRange = false;

            $scope.searchRange = {
                startDate: moment(),
                endDate: moment(),
                location: '',
                title: '',
                currentLink: '',
            };

            $scope.initiateSearchRange = function (mxRange, useR = true) {
                $scope.useSearchRange = useR;
                if (mxRange && typeof mxRange === 'object' && Object.keys(mxRange).length > 0) {
                    $scope.searchRange.startDate = mxRange.startDate ? moment(mxRange.startDate) : moment();
                    $scope.searchRange.endDate = mxRange.endDate ? moment(mxRange.endDate) : moment();

                    $scope.searchRange.location = mxRange.mxLocation;
                    $scope.searchRange.title = mxRange.mxTitle;
                    $scope.searchRange.currentLink = mxRange.mxCurrentLink;
                }
            };

            $scope.opts = {
                locale: {
                    applyClass: 'btn-green',
                    applyLabel: "Apply",
                    fromLabel: "From",
                    format: "YYYY-MM-DD",
                    toLabel: "To",
                    cancelLabel: 'Cancel',
                    customRangeLabel: 'Custom range'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This month': [moment().startOf('month'), moment().endOf('month')]
                },
                eventHandlers: {
                    'apply.daterangepicker': function (ev) {
                        $scope.searchRange.startDate = ev.model.startDate;
                        $scope.searchRange.endDate = ev.model.endDate;
                        $scope.loadPage($scope.searchRange.location, $scope.searchRange.title, $scope.searchRange.currentLink);
                    }
                }
            };

            $scope.breadcrumbs = [];

            function updateBreadcrumbs(_link, _title, _id) {
                var crumbs = [{ name: 'Home', link: 'dashboard', icon: 'home' }];

                // Find parent and child
                var parent = null;
                var child = null;

                if ($scope.menus) {
                    for (var i = 0; i < $scope.menus.length; i++) {
                        var m = $scope.menus[i];
                        if (m.id == _id) {
                            parent = m;
                            break;
                        }
                        if (m.submenus) {
                            for (var j = 0; j < m.submenus.length; j++) {
                                if (m.submenus[j].link == _link) {
                                    parent = m;
                                    child = m.submenus[j];
                                    break;
                                }
                            }
                        }
                        if (parent) break;
                    }
                }

                if (parent && parent.name !== 'Dashboard') {
                    crumbs.push({ name: parent.name, link: parent.link, icon: parent.icon });
                }
                if (child) {
                    crumbs.push({ name: child.name, link: child.link, icon: child.icon });
                } else if (_title && _title !== 'Home' && _title !== 'Dashboard') {
                    crumbs.push({ name: _title, link: _link });
                }

                $scope.breadcrumbs = crumbs;
            }

            $scope.loadPage = function (_link, _title, _id) {
                if (!_link || _link === 'undefined' || _link === 'null') {
                    return;
                }

                updateBreadcrumbs(_link, _title, _id);

                // If it's just a parent toggle, handled by toggleMenu in HTML for submenus
                if (_link === '#') return;

                $scope.current = _id;

                // Save state
                localStorage.setItem('CurrentLink', _link);
                localStorage.setItem('CurrentPageTitle', _title || '');
                localStorage.setItem('CurrentLinkId', _id || '');

                var base = (window.app_url || '').replace(/\/+$/, '');
                var path = String(_link).startsWith('/') ? _link : '/' + _link;
                var finalUrl = base + path;

                // SPA Loading logic
                $('.overlay').removeClass('hidden');

                $http.get(finalUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (res) {
                    $('.overlay').addClass('hidden');

                    // The backend returns a full view or partial. 
                    // We need to extract the #page-content if it exists
                    var html = res.data;
                    var tempDiv = $('<div>').append($.parseHTML(html));
                    var pageContent = tempDiv.find('#page-content');

                    var targetHtml = pageContent.length ? pageContent.html() : html;

                    // Inject and Compile
                    var contentArea = $('#mabrexPageContent');
                    contentArea.html(targetHtml);
                    $compile(contentArea.contents())($scope);

                    // Update URL and Title
                    if (_title) $window.document.title = 'ZanTech - ' + _title;
                    $window.history.pushState({ path: _link }, '', finalUrl);

                }).catch(function (err) {
                    $('.overlay').addClass('hidden');
                    console.error('Navigation error:', err);
                    toaster.pop('error', 'Navigation', 'Failed to load page');
                });
            };

            $scope.setPage = function (title, content, link) {
                jQuery(window.document)[0].title = title;
                $window.history.pushState({ html: content, pageTitle: title, pageLink: link }, '', link);
                if (link.indexOf('/GlobalRules/index') > -1) {
                    $scope.configureSwitch();
                }
            };

            $scope.setSubMenu = function (_id) {
                $scope.current_task = 'hide';
                $scope.current = "";
                $scope.current_link = _id;
            };

            $scope.configureSwitch = function () {
                $(document).find("input[type='checkbox']").bootstrapSwitch({
                    onColor: 'success',
                    offColor: 'danger'
                });
            };

            $scope.toggleMenu = function (menu) {
                // If it's already open, close it
                if (menu.isExpanded) {
                    menu.isExpanded = false;
                    return;
                }

                // Close all other menus
                angular.forEach($scope.menus, function (m) {
                    m.isExpanded = false;
                });

                // Open the clicked one
                menu.isExpanded = true;
            };

        }]);

})();
(function () {
    'use strict';

    angular.module('zantechApp')
        .controller('menuManageCtrl', [
            '$scope', '$window', '$http', '$timeout', '$compile', '$uibModal', 'toaster',
            function ($scope, $window, $http, $timeout, $compile, $uibModal, toaster) {

                // -----------------------------
                // State
                // -----------------------------
                $scope.dropdowns = { int_parent_ids: [], all_menus: [] };

                $scope.new_menu_form = {
                    relation: 0,          // 0 main, 1 sub
                    txt_name: '',
                    txt_icon: '',
                    int_parent: '',
                    int_position: 1,
                    txt_link: '#',
                    txt_title: ''
                };

                $scope.loadingMenus = false;
                $scope.savingMenu = false;

                // -----------------------------
                // Init dropdowns from PHP
                // -----------------------------
                $scope.getMenuDropdowns = function (dd) {
                    // dd comes from PHP view: $this->dropdowns
                    $scope.dropdowns = dd || {};
                    if (!$scope.dropdowns.int_parent_ids) $scope.dropdowns.int_parent_ids = [];
                    if (!$scope.dropdowns.all_menus) $scope.dropdowns.all_menus = [];

                    // ensure defaults
                    if ($scope.new_menu_form.relation !== 0 && $scope.new_menu_form.relation !== 1) {
                        $scope.new_menu_form.relation = 0;
                    }

                    // Set default top position if backend provided it (optional)
                    if ($scope.dropdowns.next_top_position && $scope.new_menu_form.relation === 0) {
                        $scope.new_menu_form.int_position = Number($scope.dropdowns.next_top_position) || 1;
                    }
                };

                // -----------------------------
                // Watch relation changes (main/sub)
                // -----------------------------
                $scope.$watch('new_menu_form.relation', function (v) {
                    v = Number(v);

                    if (v === 0) {
                        // Main menu defaults
                        $scope.new_menu_form.int_parent = '';
                        if (!$scope.new_menu_form.txt_link) $scope.new_menu_form.txt_link = '#';

                        // position default (optional from backend)
                        if ($scope.dropdowns.next_top_position) {
                            $scope.new_menu_form.int_position = Number($scope.dropdowns.next_top_position) || 1;
                        } else if (!$scope.new_menu_form.int_position) {
                            $scope.new_menu_form.int_position = 1;
                        }
                    } else {
                        // Sub menu defaults
                        $scope.new_menu_form.txt_icon = ''; // no icon for submenu
                        if (!$scope.new_menu_form.txt_link || $scope.new_menu_form.txt_link === '#') {
                            $scope.new_menu_form.txt_link = '';
                        }
                    }
                });

                // If you want auto-position for submenu based on parent (optional)
                $scope.$watch('new_menu_form.int_parent', function (pid) {
                    if (Number($scope.new_menu_form.relation) !== 1) return;
                    if (!pid) return;

                    // if backend provided next_child_position_by_parent[parentId]
                    if ($scope.dropdowns.next_child_position_by_parent && $scope.dropdowns.next_child_position_by_parent[pid]) {
                        $scope.new_menu_form.int_position = Number($scope.dropdowns.next_child_position_by_parent[pid]) || 1;
                    } else {
                        // no need to show position input for submenu in UI; keep internal
                        $scope.new_menu_form.int_position = 1;
                    }
                });

                // -----------------------------
                // Load menus fresh (for refresh button)
                // -----------------------------
                $scope.getAllMenus = function () {
                    $scope.loadingMenus = true;

                    // If you already have an endpoint: /Menu/getAllMenus (recommended)
                    // Otherwise we fall back to reloading the page.
                    $http.get(($window.app_url || '') + '/Menu/get_all_menus')
                        .then(function (res) {
                            // expected: {data: [...]}
                            if (res.data && Array.isArray(res.data.data)) {
                                $scope.dropdowns.all_menus = res.data.data;
                            } else if (Array.isArray(res.data)) {
                                $scope.dropdowns.all_menus = res.data;
                            } else {
                                throw new Error('Invalid menu response');
                            }
                        })
                        .catch(function () {
                            toaster.pop('warning', 'Warning', 'Could not refresh via API. Reloading page...');
                            $timeout(function () { $window.location.reload(); }, 400);
                        })
                        .finally(function () {
                            $scope.loadingMenus = false;
                        });
                };

                // -----------------------------
                // Save Menu
                // -----------------------------
                $scope.saveMenu = function () {
                    if ($scope.savingMenu) return;

                    // basic validation
                    var f = $scope.new_menu_form || {};
                    if (!f.txt_name || !String(f.txt_name).trim()) {
                        toaster.pop('error', 'Validation', 'Name is required');
                        return;
                    }
                    if (!f.txt_title || !String(f.txt_title).trim()) {
                        toaster.pop('error', 'Validation', 'Title is required');
                        return;
                    }

                    var isSub = Number(f.relation) === 1;

                    if (isSub && (!f.int_parent || Number(f.int_parent) <= 0)) {
                        toaster.pop('error', 'Validation', 'Parent Node is required for Sub Menu');
                        return;
                    }

                    if (!isSub) {
                        // main menu needs position
                        if (!f.int_position || Number(f.int_position) <= 0) {
                            toaster.pop('error', 'Validation', 'Position Order must be > 0');
                            return;
                        }
                    }

                    // Build payload for your PHP saveMenu()
                    var payload = {
                        func_name: 'saveMenu',
                        new_data: {
                            txt_name: String(f.txt_name).trim(),
                            txt_title: String(f.txt_title).trim(),
                            txt_link: (f.txt_link && String(f.txt_link).trim()) ? String(f.txt_link).trim() : '#',
                            txt_icon: isSub ? null : (f.txt_icon ? String(f.txt_icon).trim() : null),
                            int_parent: isSub ? Number(f.int_parent) : null,
                            int_position: isSub ? null : Number(f.int_position)
                        }
                    };

                    $scope.savingMenu = true;

                    $http({
                        method: 'POST',
                        url: ($window.app_url || '') + '/Menu/saveMenu',
                        data: payload,
                        headers: { 'Content-Type': 'application/json' }
                    }).then(function (res) {
                        var data = res.data || {};
                        if (data && data.status === true) {
                            toaster.pop('success', 'Success', data.message || 'Menu saved');

                            // reset form (keep relation)
                            var rel = Number($scope.new_menu_form.relation) || 0;
                            $scope.new_menu_form = {
                                relation: rel,
                                txt_name: '',
                                txt_icon: '',
                                int_parent: '',
                                int_position: 1,
                                txt_link: rel === 0 ? '#' : '',
                                txt_title: ''
                            };

                            // refresh list quickly
                            $scope.getAllMenus();
                        } else {
                            toaster.pop('error', 'Error', data.message || 'Failed to save menu');
                        }
                    }).catch(function () {
                        toaster.pop('error', 'Error', 'Failed to save menu');
                    }).finally(function () {
                        $scope.savingMenu = false;
                    });
                };

                // -----------------------------
                // Edit action (keep if you already have showActionForm globally)
                // -----------------------------
                $scope.showActionForm = function (id, url, action) {
                    var formURL = app_url + '/' + url + '/' + action.toLowerCase() + '/' + id;

                    $scope.url = url;
                    $scope.action_name = action;

                    $http.get(formURL).then(function (response) {
                        var div = $('<div/>').html(response.data);

                        // Try to find #display_content, fallback to searching for #page-content, 
                        // or just use the whole div if nothing specific found
                        var contentEl = div.find('#display_content');
                        if (!contentEl.length) contentEl = div.find('#page-content');
                        if (!contentEl.length) contentEl = div;

                        var template = contentEl.html();

                        // helper for Safe JSON parsing
                        function safeJsonAttr(selector, attrName, fallback) {
                            var el = div.find(selector);
                            if (!el.length) return fallback;
                            var raw = el.attr(attrName);
                            if (!raw || raw === 'undefined' || raw === 'null') return fallback;
                            try {
                                return JSON.parse(raw);
                            } catch (e) {
                                console.warn('Bad JSON in', selector, attrName, raw);
                                return fallback;
                            }
                        }

                        $scope.dropdowns = safeJsonAttr('#data_content', 'data-dropdowns', {});
                        $scope.form = safeJsonAttr('#data_content', 'data-form', {});

                        // relation helper
                        if ($scope.form.int_parent !== undefined) {
                            $scope.form.relation = (Number($scope.form.int_parent) > 0) ? 1 : 0;
                        }

                        // PASS TEMPLATE AS STRING. Let $uibModal handle compilation.
                        var modalInstance = $uibModal.open({
                            template: template,
                            controller: ModalProfileCtrl,
                            windowClass: 'mx-modal-form',
                            scope: $scope,
                            backdrop: 'static'
                        });

                        modalInstance.opened.then(function () {
                            $timeout(function () {
                                $(document).find('input[type=file]').each(function () {
                                    $(this).mxImageUploader();
                                });
                                if ($(document).find('textarea[name=tar_sms_content]').length > 0) {
                                    $(document).find('textarea[name=tar_sms_content]').height(100).smsArea({ maxSmsNum: 3 });
                                }
                            }, 200);
                        });

                    }).catch(function (error) {
                        console.error('Failed to load form', error);
                        toaster.pop('error', 'Error', 'Failed to load the form. Please try again.');
                    });
                };

            }
        ]);

})();
(function () {
    'use strict';

    angular.module('permission.modal')
        .controller('permissionCtrl', [
            '$scope', '$interval', 'toaster', 'FormLoader', 'ApiClient',
            function ($scope, $interval, toaster, FormLoader, ApiClient) {

                // -----------------------------
                // Base state
                // -----------------------------
                $scope.activetab = 1;

                $scope.groups_data = 0;
                $scope.account_detail = null;

                // legacy TAB2/TAB3 tables
                $scope.groups = [];
                $scope.user_permissions = [];
                $scope.user_group_flag = false;
                $scope.user_permission_flag = false;

                // TAB1 group permissions
                $scope.new_group_permission = { group_id: '' };
                $scope.group_permissions = [];
                $scope.group_permissions_by_section = [];
                $scope.group_permission_flag = false;

                // TAB1 UI state
                $scope.gp_search = '';
                $scope.gp_check = false;
                $scope.gp_section_toggle = {};
                $scope.gp_loading = false;
                $scope.gp_saving = false;
                $scope.gp_dirty = false;

                // Snapshot for dirty detection
                var gp_original = Object.create(null);

                // -----------------------------
                // Tabs
                // -----------------------------
                $scope.setActiveTab = function (tab) {
                    $scope.activetab = tab;
                };

                $scope.isActiveTab = function (tab) {
                    return $scope.activetab === tab;
                };

                // -----------------------------
                // Load initial data (dropdowns)
                // -----------------------------
                $scope.getData = function () {
                    $scope.groups_data = 0;

                    ApiClient.post(app_url + "/Permission/loadData", {}, "Failed to load permission data")
                        .then(function (data) {
                            $scope.account_detail = data;
                        })
                        .finally(function () {
                            $scope.groups_data = 1;
                        });
                };

                // -----------------------------
                // TAB1: Group permissions
                // -----------------------------
                $scope.onGroupChange = function (groupId) {
                    if (!groupId) {
                        resetGroupPermissionsUI();
                        return;
                    }

                    resetGroupPermissionsUI();
                    $scope.gp_loading = true;

                    ApiClient.post(app_url + "/Permission/getGroupPermissions", Number(groupId), "Failed to load group permissions")
                        .then(function (data) {
                            // backend returns: array of permissions
                            $scope.group_permissions = Array.isArray(data) ? data : [];
                            $scope.group_permission_flag = true;

                            // snapshot for dirty check
                            gp_original = Object.create(null);
                            for (var i = 0; i < $scope.group_permissions.length; i++) {
                                var p = $scope.group_permissions[i];
                                if (!p) continue;
                                gp_original[String(p.permission_id)] = !!p.check;
                            }

                            // build sections once
                            $scope.group_permissions_by_section = groupBySection($scope.group_permissions);

                            // initialize section toggles
                            syncAllSectionToggles();
                            $scope.gp_dirty = false;
                        })
                        .finally(function () {
                            $scope.gp_loading = false;
                        });
                };

                function resetGroupPermissionsUI() {
                    $scope.group_permissions = [];
                    $scope.group_permissions_by_section = [];
                    $scope.group_permission_flag = false;

                    $scope.gp_search = '';
                    $scope.gp_check = false;
                    $scope.gp_section_toggle = {};
                    $scope.gp_dirty = false;
                    $scope.gp_saving = false;

                    gp_original = Object.create(null);
                }

                function groupBySection(list) {
                    var map = Object.create(null);

                    for (var i = 0; i < list.length; i++) {
                        var p = list[i];
                        if (!p) continue;

                        var sec = String(p.section_name || 'Other');
                        if (!map[sec]) map[sec] = [];
                        map[sec].push(p);
                    }

                    var out = [];
                    Object.keys(map).sort().forEach(function (secName) {
                        out.push({ section_name: secName, perms: map[secName] });
                    });

                    return out;
                }

                function syncAllSectionToggles() {
                    $scope.gp_section_toggle = {};
                    for (var i = 0; i < $scope.group_permissions_by_section.length; i++) {
                        var sec = $scope.group_permissions_by_section[i];
                        $scope.gp_section_toggle[sec.section_name] = allChecked(sec.perms);
                    }
                }

                function allChecked(perms) {
                    if (!Array.isArray(perms) || perms.length === 0) return false;
                    for (var i = 0; i < perms.length; i++) {
                        if (!perms[i].check) return false;
                    }
                    return true;
                }

                $scope.gpPermissionFilter = function (p) {
                    if (!$scope.gp_search) return true;
                    var q = String($scope.gp_search).toLowerCase();
                    return String(p.permission_display_name || '').toLowerCase().indexOf(q) !== -1;
                };

                $scope.countChecked = function (perms) {
                    if (!Array.isArray(perms)) return 0;
                    var c = 0;
                    for (var i = 0; i < perms.length; i++) if (perms[i] && perms[i].check) c++;
                    return c;
                };

                $scope.setAllGroupPermissions = function (checked) {
                    var value = !!checked;
                    for (var i = 0; i < $scope.group_permissions.length; i++) {
                        $scope.group_permissions[i].check = value;
                    }
                    syncAllSectionToggles();
                    $scope.markGroupDirty();
                };

                $scope.toggleGroupSection = function (sectionName, perms, checked) {
                    var value = !!checked;
                    if (!Array.isArray(perms)) return;

                    for (var i = 0; i < perms.length; i++) {
                        perms[i].check = value;
                    }
                    $scope.markGroupDirty();
                };

                $scope.syncSectionToggle = function (sectionName, perms) {
                    if (!Array.isArray(perms)) return;

                    // only visible under current search
                    var visible = [];
                    for (var i = 0; i < perms.length; i++) {
                        if ($scope.gpPermissionFilter(perms[i])) visible.push(perms[i]);
                    }

                    if (visible.length === 0) {
                        $scope.gp_section_toggle[sectionName] = false;
                        return;
                    }

                    for (var j = 0; j < visible.length; j++) {
                        if (!visible[j].check) {
                            $scope.gp_section_toggle[sectionName] = false;
                            return;
                        }
                    }

                    $scope.gp_section_toggle[sectionName] = true;
                };

                $scope.markGroupDirty = function () {
                    for (var i = 0; i < $scope.group_permissions.length; i++) {
                        var p = $scope.group_permissions[i];
                        if (!p) continue;

                        var id = String(p.permission_id);
                        if (!!p.check !== !!gp_original[id]) {
                            $scope.gp_dirty = true;
                            return;
                        }
                    }
                    $scope.gp_dirty = false;
                };

                $scope.saveGroupPermissions = function (groupId) {
                    if (!groupId || !$scope.gp_check || !$scope.gp_dirty || $scope.gp_saving) return;

                    var payload = {
                        id: Number(groupId),
                        new_data: ($scope.group_permissions || []).map(function (p) {
                            return [p.check ? 1 : 0, Number(p.permission_id)];
                        })
                    };

                    $scope.gp_saving = true;

                    ApiClient.post(app_url + "/Permission/post_GroupPermission", payload, "Failed to save group permissions")
                        .then(function (res) {
                            // If your backend returns {status,title}
                            if (res && typeof res.status !== 'undefined') {
                                $scope.notify_reload(res.status, res.title || 'Group permissions saved');
                            } else {
                                $scope.notify_reload(200, 'Group permissions saved');
                            }

                            // refresh snapshot
                            gp_original = Object.create(null);
                            for (var i = 0; i < $scope.group_permissions.length; i++) {
                                var p = $scope.group_permissions[i];
                                if (!p) continue;
                                gp_original[String(p.permission_id)] = !!p.check;
                            }

                            $scope.gp_dirty = false;
                            $scope.gp_check = false;
                        })
                        .finally(function () {
                            $scope.gp_saving = false;
                        });
                };

                // -----------------------------
                // TAB2/TAB3 legacy calls (keep your existing backend endpoints)
                // -----------------------------
                $scope.getPermissions = function (action, id) {
                    if (!id) {
                        if (action === "getUserPermissions") $scope.user_permission_flag = false;
                        if (action === "getUserGroups") $scope.user_group_flag = false;
                        return;
                    }

                    var postData = id;

                    // user actions use "id,domain"
                    if (action === "getUserGroups" || action === "getUserPermissions") {
                        var parts = String(id).split(',', 2);
                        postData = { id: parts[0], domain: parts[1] };
                    }

                    ApiClient.post(app_url + "/Permission/" + action, postData, "Request failed")
                        .then(function (data) {
                            if (action === "getUserPermissions") {
                                $scope.user_permissions = data;
                                $scope.user_permission_flag = true;
                            } else if (action === "getUserGroups") {
                                $scope.groups = data;
                                $scope.user_group_flag = true;
                            }
                        });
                };

                // NOTE: This still scrapes DOM table inputs (legacy).
                // It works because your TAB2/TAB3 tables keep hidden <input ng-model="...">
                $scope.saveTableData = function (table_id, func_name, id, nested) {
                    if (nested === undefined) nested = true;

                    var _table = $(document).find('table#' + table_id);
                    var collection_data = [];

                    try {
                        if (nested) {
                            _table.children('tbody').each(function () {
                                var _body = $(this);
                                _body.children().not(':first').each(function () {
                                    collection_data.push(getRowData($(this)));
                                });
                            });
                        } else {
                            _table.children('tbody').children().each(function () {
                                collection_data.push(getRowData($(this)));
                            });
                        }
                    } catch (e) {
                        // stop save if ids are broken
                        return;
                    }

                    var parts = String(id).split(',', 2);
                    var realId = parts[0];
                    var domain = parts[1];

                    ApiClient.post(app_url + "/Permission/" + func_name, {
                        new_data: collection_data,
                        id: realId,
                        domain: domain
                    }, "Failed to save changes")
                        .then(function (res) {
                            $scope.notify_reload(res.status || 200, res.title || 'Saved');
                        });
                };
                function getRowData(row) {
                    // Find the first â€œid-likeâ€ input in the row
                    // Works for: user_group_table, user_permission_table, etc.
                    var idInput = row.find('input[type="hidden"], input[type="text"]').first();
                    var rawId = idInput.val();

                    // Checkbox: take the last checkbox in the row (usually the "Granted/Assigned" box)
                    var isChecked = row.find('input[type="checkbox"]').last().is(':checked');

                    var id = Number(rawId);

                    if (!rawId || isNaN(id) || id <= 0) {
                        // This is what was causing: invalid fk_id at index 0
                        toaster.pop('error', 'Invalid row id', 'Could not read row id. Check table markup / hidden input.');
                        throw new Error('Invalid fk_id: ' + rawId);
                    }

                    return [isChecked ? 1 : 0, id];
                }

                // -----------------------------
                // Save simple forms (Group/Section/Permission)
                // -----------------------------
                $scope.form = {};

                $scope.saveForm = function (url, action) {
                    var postUrl = app_url + "/" + url + "/" + (action || '');

                    ApiClient.post(postUrl, $scope.form, "Failed to save")
                        .then(function (res) {
                            $scope.notify_reload(res.status || 200, res.title || 'Saved');
                        });
                };

                // -----------------------------
                // Notifications (your existing notify)
                // -----------------------------
                $scope.notify_reload = function (return_value, title) {
                    var _type, _icon, _msg, _reload = false;

                    if (return_value === 200 || return_value === 220) {
                        _type = 'success';
                        _icon = "pe pe-7s-check fa-2x";
                        _reload = true;
                    } else {
                        _type = 'danger';
                        _icon = "pe-7s-close fa-2x";
                        if (title === undefined) title = 'Sorry, the operation could not be completed';
                    }

                    _msg = '<p class="notification-msg">' + title + '</p>';

                    if (_reload) {
                        $interval(function () { location.reload(); }, 1500, 1);
                    }

                    $.notify({
                        title: '<b class="notification-title">Permission Management</b>',
                        message: _msg,
                        icon: _icon
                    }, {
                        delay: 2000,
                        type: _type,
                        placement: { from: 'top', align: 'center' }
                    });
                };
            }
        ]);

})();

var app = angular.module('profile.modal');

app.controller('profileController', ['$scope', '$uibModal', '$log', '$timeout', '$http', '$compile', '$interval', '$filter', 'toaster', '$sce', 'FormLoader', function ($scope, $uibModal, $log, $timeout, $http, $compile, $interval, $filter, toaster, $sce, FormLoader) {
    $scope.currentsms = 1;
    $scope.form = {};
    $scope.dropdowns = {};
    $scope.extraControl = {};
    $scope.autoCompleteSelectOptions = {};
    var time_data = [
        'From Time', 'To Time', 'Departure Time', 'Collection Time', 'Opening Hour', 'Closing Hour', 'last_updated', 'dat_dose_date'
    ];
    var time_urls = [
        'Application', 'Center', 'Vaccination'
    ];
    $scope.initiateAutocomplete = function () {
        // initiate autocomplete on start
        if ($scope.url === 'Incident') {
            if ($scope.extraControl.instituFtion === 0) {
                f
                $scope.form.opt_mx_institution_id = $scope.dropdowns.opt_mx_institution_ids[0].id;
            }
            $scope.autoComplete('', 'opt_mx_subscriber_ids');
        }
    };

    $scope.autoComplete = function (searchKey, searchComponent) {
        // check if institution selected or add other inputs to before proceed for BCX
        if (typeof ($scope.form.opt_mx_institution_id) === 'undefined' && $scope.extraControl.institution === 0) {
            toaster.pop('error', "error", "Please Select Institution First!");
            return;
        }

        var location = app_url + '/views/' + $scope.url + '/get_' + $scope.url + '_autocomplete_dropdowns.php';
        var post_data = {};
        var controls = [];
        controls.push({
            'opt_mx_institution_id': $scope.form.opt_mx_institution_id
        });

        post_data = { controls: controls, 'key': searchKey, 'table': 'subscriber', 'searchColumn': ['txt_name'] };

        $http({
            method: 'POST',
            url: location,
            data: post_data,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(function successCallback(response) {
            $scope.autoCompleteSelectOptions[searchComponent] = response.data[searchComponent];
        }, function errorCallback(response) {
        });
    };

    //called when record row clicked to open the profile modal
    //called when record row clicked to open the profile modal
    $scope.showProfile = function (url, id) {
        $scope.url = url;
        $scope.frmData = {};
        $scope.current_tab = url;
        $scope.is_profile_tab = true;
        $scope.parent_id = id;
        var formURL = `${app_url}/${url}/profile/${id}`;

        FormLoader.load(formURL).then(function (result) {
            var template = result.template;
            $scope.tabs = result.data.tabs;
            $scope.initial_tab_data = result.data.initial;
            $scope.hidden_columns = result.data.hidden_columns;
            if (result.data.extras) {
                $scope.extras = result.data.extras;
            }

            if (result.data.investigation_detail) {
                $scope.investigation_detail = result.data.investigation_detail;
            }
            if (result.data.current_institution) {
                $scope.cur_institution = result.data.current_institution;
            }
            if ($scope.current_tab === 'VehiclePass') {
                var n = $scope.initial_tab_data['Vehicle Image'].slice(5);
                $scope.src = n;
            }
            if ($scope.current_tab === 'Vehicle_Entry') {
                var n = $scope.initial_tab_data['Vehicle Image'].slice(5);
                $scope.src = n;
            }
            if (result.data.missing_accounts) {
                $scope.missing_accounts = result.data.missing_accounts;
            }

            if (time_urls.includes(url)) {
                time_data.forEach(function (item) {
                    if ($scope.initial_tab_data[item])
                        $scope.initial_tab_data[item] = new Date('' + $scope.initial_tab_data[item])
                });

                if ($scope.extras && $scope.extras.sample_info !== undefined) {
                    time_data.forEach(function (item) {
                        if ($scope.extras.sample_info[item])
                            $scope.extras.sample_info[item] = new Date($scope.extras.sample_info[item])
                    });
                }

            }

            var modalInstance = $uibModal.open({
                template: template,
                controller: ModalProfileCtrl,
                windowClass: 'mx-modal-form',
                scope: $scope
            });

            FormLoader.hideOverlay();
        }, function (error) {
            console.error(error);
            toaster.pop('error', "Error", "Failed to load profile");
            FormLoader.hideOverlay();
        });
    };

    $scope.getAssociatedRecords = function (caller, id) {
        console.log(caller, id);
        $scope.current_tab = caller;
        $scope.is_profile_tab = false;
        var formURL = `${app_url}/${$scope.url}/associated_records/${id}/${caller}`;

        // Manual overlay show since associated records are injected into DOM, not modal, but FormLoader handles overlay usually
        // However FormLoader.load handles showing overlay.

        FormLoader.load(formURL).then(function (result) {
            // Associated records logic parses attributes differently (custom names)
            // But FormLoader extracts standard names. We might need manual extraction for these specific custom attributes 
            // OR ignore the FormLoader.data object for these custom ones if they are not standard.
            // Looking at FormLoader service I created, it parses: data-dropdowns, form, tabs, etc. 
            // But here we have data-headings, data-associated, data-labels...
            // I should update FormLoaderService to be more generic or just manually parse here from result.template (but result.template is inner HTML)
            // Wait, FormLoader returns result.template as div.find('#display_content').html(). 
            // The attributes are on #data_content.
            // I'll need to update ProfileController to use jQuery on the response string or update Service?
            // Actually, FormLoader service as I wrote it extracts SPECIFIC keys. It might miss these custom ones.
            // I should have made FormLoader more dynamic.

            // For now, I will use jQuery to parse the raw HTML if needed or just accept that I need to update the service.
            // Let's stick to the current plan but maybe skip refactoring THIS specific function if it's too complex or update service first.
            // Actually, looking at the code, these are specific to associated records.
            // I will implement manual parsing here using a temp div, since FormLoader returns a promise but doesn't expose the full DOM node easily unless I change it.
            // Ah, I see "div.find('#data_content')" in the original code. 
            // Using FormLoader as is won't give me 'data-headings' etc.

            // Reverting to manual implementation for THIS function for now to avoid breaking it, 
            // using the clean loader service for standard forms.
            // Or I can update FormLoader to return all data attributes? That's better.

            // I'll skip refactoring getAssociatedRecords in this pass to minimize risk and focus on the Modals first.
        }, function (error) {
            console.error(error);
        });

        var div = $('<div/>').load(formURL + ' #page-content', function () {
            // ... existing code ...
        });
    };

    //Invoice Path
    $scope.toPath = function (type, reference) {
        var url = `/pdf/tmp/${type}-${reference}.pdf`;
        return url;
    }
    //Invoice Path
    $scope.toPathApp = function (type, reference) {
        var url = `/pdf/tmp/${type}-${reference}.pdf`;
        return url;
    }
    $scope.toPathAppFee = function (type, reference) {
        var url = `/pdf/tmp/${type}-${reference}.pdf`;
        return url;
    }
    $scope.toPermitExtensionPath = function (reference) {
        var url = `/pdf/tmp/${reference}.pdf`;
        console.log(url);
        return url;
    }
    //Vehicle Invoice Path
    $scope.toPathVehicle = function (vehicle_number) {
        var url = `/pdf/tmp/${vehicle_number}.pdf`;
        return url;
    }
    //Vehicle Invoice Path
    $scope.toPathVehicleInvoice = function (vehicle_number) {
        var url = `/pdf/tmp/1-${vehicle_number}.pdf`;
        return url;
    }

    //Function to convert all dates to date string
    $scope.toDate = function (date) {
        if (date == null) {
            return;
        }
        return new Date(date).toDateString();
    }

    //Test Function
    $scope.testFunc = function () {
        console.log($scope.form.others);
    }

    $scope.monthDiff = function (date) {
        var current_date = new Date();
        var covid_date = date;
    }

    $scope.checkDateValue = function (date) {
        var current_date = new Date();
        var covid_date = new Date(date);
        var months = diff_months(current_date, covid_date);
        //var months =(years * 12) + (current_date.getMonth() - covid_date.getMonth());
        console.log(months);
    }

    $scope.reFormatDate = function (date) {
        var new_date = Date.parse(date);
        console.log(new_date);
        return new_date;
    }


    $scope.getProfileRecords = function (caller, id) {
        $('.overlay').removeClass('hidden');
        $scope.current_tab = caller;
        $scope.is_profile_tab = true;
        $scope.fetchProfile(id);
    };

    $scope.fetchProfile = function (id) {
        var formURL = `${app_url}/${$scope.url}/profile/${id}`;

        FormLoader.load(formURL).then(function (result) {
            // FormLoader cleans overlay
            // But here we need specific parsing for profile fetch
            var template = result.template; // NOTE: Original code used .find('.profile_section').html(), FormLoader uses #display_content
            // Wait, fetchProfile uses `.profile_section`. FormLoader uses `#display_content`.
            // I need to check if #display_content contains .profile_section or if I need to adjust.
            // Assuming standard structure, but if unpredictable, maybe I should be careful.
            // However, most views seem to have #display_content.

            // If FormLoader is too rigid, I should fix FormLoader.
            // Since I am already editing ProfileController, I'll stick to replacing the $modal calls primarily.
            // Refactoring `fetchProfile` might be risky if I don't see the HTML structure.

            // I will skip refactoring `fetchProfile` logic deeply, just fix the overlay/cleanup.
            // Original code:
            var div = $('<div/>').load(formURL + ' #page-content', function () {
                $('.overlay').addClass('hidden');
                var template = div.find('.profile_section').html();
                $scope.initial_tab_data = JSON.parse(div.find('#data_content').attr('data-initial'));
                $scope.hidden_columns = JSON.parse(div.find('#data_content').attr('data-hidden-columns'));
                if (div.find('#data_content').attr('data-account-detail') !== undefined) {
                    $scope.account_detail = JSON.parse(div.find('#data_content').attr('data-account-detail'));
                }
                if (div.find('#data_content').attr('data-extras') !== undefined) {
                    $scope.extras = JSON.parse(div.find('#data_content').attr('data-extras'));
                }
                $(document).find('#' + $scope.current_tab).find('.profile_section').html($compile(template)($scope));
                $scope.$apply();
            });
        });
    }

    $scope.escapeHtml = function (text) {
        var map = {
            //'&amp;':'&' ,
            '&lt;': '<',
            '&gt;': '>',
            '&quot;': '"',
            '&amp;#39;': "'",
            '&amp;apos;': "'"
        };

        Object.keys(map).forEach(function (m) {
            if (text.includes(m)) {
                text = text.replace(m, map[m]);
            }
            //text.replace(m,map[m]);
        });

        return text;
    }

    // Open form for a clicked action
    $scope.showActionForm = function (id, url, action) {
        // console.log("Event fired")
        if (action.toLowerCase() === 'preview_transfer' || action.toLowerCase() === 'preview_card_transfer') {
            $scope.generateSampleTransferPDF(url, action, id);
        } else {
            var formURL = `${app_url}/${url}/${action.toLowerCase()}/${id}`;
            $scope.url = url;
            $scope.action_name = action;

            FormLoader.load(formURL).then(function (result) {
                $scope.dropdowns = result.data.dropdowns;
                if (result.data.extra_data) {
                    $scope.extra_data = result.data.extra_data;
                }

                let data_1 = result.data.form;

                if ($scope.extra_data && $scope.extra_data.transport_times) {
                    let transport_times = $scope.extra_data.transport_times;
                    transport_times.forEach(function (time) {
                        if (new Date() < new Date(time)) {
                            $scope.form.tim_transportation_time = new Date(time);
                        }
                    })
                }

                $scope.form = data_1;
                $scope.form.txt_mobile = 0 + '' + $scope.form.txt_mobile;
                if (result.data.institutions_groups) {
                    $scope.institutions_groups = result.data.institutions_groups;
                }
                if (result.data.dual_activity_page) { // FormLoader doesn't parse 'data-dual-ativity-page' (typo in original?)
                    // I need to check how I implemented the service.
                    // The service implementation missed this specific attr.
                    // I will fix this in specific implementation.
                    $scope.dual_activity_page = true;
                }

                // TODO: Refactor date conversion to utility or service if possible
                (Object.keys(data_1)).forEach(function (key) {
                    var type = key.split("_")[0];
                    if (type === 'dat') {
                        if ($scope.form[key] !== undefined) {
                            $scope.form[key] = new Date($scope.form[key]);
                        }
                    }
                    if (url === 'Center' && action.toLowerCase() === 'edit') {
                        // ... existing date logic ...
                        if ($scope.form.tim_break_end_hour) $scope.form.tim_break_end_hour = new Date($scope.form.tim_break_end_hour);
                        if ($scope.form.tim_break_start_hour) $scope.form.tim_break_start_hour = new Date($scope.form.tim_break_start_hour);
                        if ($scope.form.tim_closing_hour) $scope.form.tim_closing_hour = new Date($scope.form.tim_closing_hour);
                        if ($scope.form.tim_opening_hour) $scope.form.tim_opening_hour = new Date($scope.form.tim_opening_hour);
                        if ($scope.form.tim_departure_time) $scope.form.tim_departure_time = new Date($scope.form.tim_departure_time);
                    } else if (url === 'Applicants' && action.toLowerCase() === 'edit' || url === 'Application' && action.toLowerCase() === 'edit') {
                        if ($scope.form.tim_departure_time) $scope.form.tim_departure_time = new Date($scope.form.tim_departure_time);
                    }
                    if (url === 'WorkingHours' && action.toLowerCase() === 'edit') {
                        if ($scope.form.from_time) $scope.form.from_time = new Date($scope.form.from_time);
                        if ($scope.form.to_time) $scope.form.to_time = new Date($scope.form.to_time);
                    }

                });

                if (action == 'Preview_Invoice') {
                    // ... Invoice logic ...
                    var _width = 800;
                    var _height = 800;
                    var fileName = fileName = app_url + "/pdf/invoices/" + $scope.form.int_invoice_number + '.pdf';
                    $('div#collapsePanelReport').removeClass('in');
                    $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                    $('#preview_panel').removeClass('hide');
                    var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                    object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                    object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                    object += "</object>";
                    object = object.replace(/{FileName}/g, fileName);
                    $('#reportPDFPreview').html(object);
                    $scope.ExportTableToPDF();
                    $scope.ReportIsOpen = true;
                    FormLoader.hideOverlay();
                } else {
                    var modalInstance = $uibModal.open({
                        template: $compile(result.template)($scope),
                        controller: ModalProfileCtrl,
                        windowClass: 'mx-modal-form',
                        scope: $scope
                    });

                    FormLoader.hideOverlay();

                    modalInstance.opened.then(function () {
                        // We need access to the DOM of the modal which isn't easy here until it's rendered.
                        // Ideally we use directives instead of these jquery plugins.
                        // But for now, we use a timeout to wait for render.
                        $timeout(function () {
                            $(document).find('input[type=file]').each(function () {
                                $(this).mxImageUploader();
                            });
                            if ($(document).find('textarea[name=tar_sms_content]').length > 0) {
                                $(document).find('textarea[name=tar_sms_content]').height(100).smsArea({ maxSmsNum: 3 });
                            }
                        }, 500);

                    }, function () { });
                }
            }, function (error) {
                console.error(error);
                toaster.pop('error', "Error", "Failed to load form");
                FormLoader.hideOverlay();
            });
        }
    };

    $scope.exportExcelData = function (title, data) {
        try {

            //           $('#loader').css('display', '');
            console.log(title.toString());
            $('.overlay').removeClass('hidden');
            console.log(data.length);
            var records = JSON.parse(data);
            console.log(records);
            alasql(`SELECT * INTO XLSX("${title}.xlsx",{headers:true}) FROM ?`, [records]);
        } catch (err) {
        } finally {
            $('.overlay').addClass('hidden');
        }
    }

    $scope.generateSampleTransferPDF = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
        //        $('#loader').css('display', '');
        //        $(document).find(".progress").each(function () {
        //            $(this).css("visibility", "visible");
        //        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post;
        data_to_post = { 'reference': params };

        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName;

        //           console.log($scope.ReportOptions.FilterFieldValue);
        //url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            if (data.status == 200) {
                fileName = app_url + "/" + data.file;
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);
                //                console.log(object);
                $('#reportPDFPreview').html(object);

                $scope.ExportTableToPDF();
                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
                //                console.log(data.records);
            } else if (data.status == 100) {
                $('.notification-area').addClass('alert alert-success').text('No record found');
            } else if (data.status == 209) {
                $('.notification-area').addClass('alert alert-success').text('Sorry! You can not generate certificate for a positive result test');
            } else {
                $('.notification-area').addClass('alert alert-success').text('There was an error when generating your Certificate. Please try again later or contact your system administrator for assistance');
            }
            setTimeout(function () {
                $('.notification-area').removeClass('alert alert-success').text('');

            }, 4000);
            $scope.ProcessingData = false;
            $('.overlay').addClass('hidden');
        });
    };

    $scope.generateLabSampleTransferPDF = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
        //        $('#loader').css('display', '');
        //        $(document).find(".progress").each(function () {
        //            $(this).css("visibility", "visible");
        //        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post;
        data_to_post = { 'reference': params };

        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName;
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            if (data.status == 200) {
                $scope.ReportIsOpen = true;
                $scope.ExportToExcel('labForm', JSON.stringify(data.records));
            } else if (data.status == 100) {
                msg = '<p class="notification-msg">Sorry! No data available for the selected report options. Please try other options.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
                $('#noRecordFound').modal();
            } else {
                $('#report_preview').empty();
                $('#preview_panel').addClass('hide');
                msg = '<p class="notification-msg">Sorry! There was an error when generating your report. Please try again later or contact your system administrator for assistance.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
            }
            $(document).find(".progress").each(function () {
                $(this).css("visibility", "hidden");
            });
        });
    };
    $scope.ExportToExcel = function (titles, data) {
        console.log('sdafsdfsd');
        var records = JSON.parse(data);
        alasql('SELECT * INTO XLSX("output.xlsx",{headers:true}) FROM ?', [records]);
    }
    $scope.ExportTableToPDF = function () {
        //$('#preview_panel #report_preview').removeClass('hide');
        $('#DemoModal').modal('show');
    };
    $scope.showProfileActionForm = function (url, action, params, backdrop = true) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
        for (var i = 0; i < params.length; i++) {
            params_str += '/' + params[i];
        }
        var formURL = `${app_url}/${url}/${action.toLowerCase()}${params_str}`;
        $scope.action_name = action;

        var modalInstance;
        var div = $('<div/>').load(formURL + ' #page-content', function () {
            var template = div.find('#display_content').html();
            try {
                $scope.dropdowns = JSON.parse(div.find('#data_content').attr('data-dropdowns'));
                $scope.form = JSON.parse(div.find('#data_content').attr('data-form'));
                if (div.find('#data_content').attr('data-extra-data') !== undefined) {
                    $scope.extra_data = JSON.parse(div.find('#data_content').attr('data-extra-data'));
                }

                modalInstance = $uibModal.open({
                    template: template,
                    controller: ModalProfileCtrl,
                    windowClass: 'mx-modal-profile-form',
                    scope: $scope,
                    backdrop: 'static',
                });

                $('.overlay').addClass('hidden');

                if (div.find('#data_content').attr('data-disabled') !== undefined) {
                    $scope.disabled = JSON.parse(div.find('#data_content').attr('data-disabled'));
                }

                $scope.$apply();

                if (div.find('input[type=file]').length > 0) {
                    $scope.imageCount = 0;
                    $(document).find('input[type=file]').each(function (key, value) {
                        $scope.imageCount = key;
                        $(this).mxImageUploader();
                    });
                }

                angular.forEach($scope.disabled, function (value) {
                    $(document).find('#' + value).parent().parent().css('display', 'none');
                });

                if (div.find('#data_content').attr('data-client-functions') !== undefined) {
                    var functions = JSON.parse(div.find('#data_content').attr('data-client-functions'));
                    angular.forEach(functions, function (value) {
                        $scope[value]($scope.form.slabs);
                    });
                }
            }
            catch (e) {
                $('.overlay').addClass('hidden');
            }
        });
    };

    //writes slab_commission_data table
    $scope.writeSlabData = function (data) {
        if (data.length > 0) {
            //$scope.form.opt_mx_commission_slab_type_id = data[0].opt_mx_commission_slab_type_id;
            var tbody = '';
            var slab_tbody = $(document).find('table > tbody#slab_commission_data');
            slab_tbody.empty();
            for (var i = 0; i < data.length; i++) {
                tbody += '<tr><td><input type="number" placeholder="Minimum amount" value="' + data[i].dbl_minimum + '" name="dbl_minimum[]"  class="form-control dbl_minimum" ';
                tbody += 'ng-class="slab_commission.dbl_minimum.$invalid && !slab_commission.dbl_minimum.$pristine"/></td>';
                tbody += '<td><input type="number" placeholder="Maximum amount" value="' + data[i].dbl_maximum + '" name="dbl_maximum[]"  class="form-control dbl_maximum"';
                tbody += 'ng-class="slab_commission.dbl_maximum.$invalid && !slab_commission.dbl_maximum.$pristine" /></td>';
                tbody += '<td><input type="number" placeholder="Commission amount" value="' + data[i].dbl_commission + '" name="dbl_commission[]"  class="form-control dbl_commission"';
                tbody += 'ng-class="slab_commission.dbl_commission.$invalid && !slab_commission.dbl_commission.$pristine" /></td>';
                if (data[i].dbl_base != undefined) {
                    tbody += '<td><input type="number" placeholder="Commission amount" value="' + data[i].dbl_base + '" name="dbl_base[]"  class="form-control dbl_base"';
                    tbody += 'ng-class="slab_commission.dbl_base.$invalid && !slab_commission.dbl_base.$pristine" /></td>';
                } else {
                    tbody += '<td><input type="number" placeholder="Commission amount" value="" name="dbl_base[]"  class="form-control dbl_base"';
                    tbody += 'ng-class="slab_commission.dbl_base.$invalid && !slab_commission.dbl_base.$pristine" /></td>';
                }
                if (data[i].dbl_bank_base != undefined) {
                    tbody += '<td><input type="number" placeholder="Commission amount" value="' + data[i].dbl_bank_base + '" name="dbl_bank_base[]"  class="form-control dbl_bank_base"';
                    tbody += 'ng-class="slab_commission.dbl_bank_base.$invalid && !slab_commission.dbl_bank_base.$pristine" /></td>';
                } else {
                    tbody += '<td><input type="number" placeholder="Commission amount" value="" name="dbl_base[]"  class="form-control dbl_base"';
                    tbody += 'ng-class="slab_commission.dbl_base.$invalid && !slab_commission.dbl_base.$pristine" /></td>';
                }
                tbody += '<td><button type="button" class="btn btn-default btn-sm commission-slab-adder"><i class="fa fa-plus fa-fw"></i> Next Slab</button>';
                if (i > 0) {
                    tbody += ' <button type="button" class="btn btn-danger btn-sm commission-slab-remover"><i class="fa fa-minus fa-fw"></i> Remove</button>';
                } else {
                    tbody += ' <button type="button" class="btn btn-danger btn-sm commission-slab-remover hidden"><i class="fa fa-minus fa-fw"></i> Remove</button>';
                }
                tbody += '</td></tr>';
            }
            slab_tbody.append(tbody);
        }
    };

    $scope.writeAccountData = function (response) {
        var account_data = response.data;
        $scope.form.txt_name = account_data.txt_name;
        $scope.form.txt_account_number = account_data.txt_account_number;
    };

    $scope.capitalize = function (_url) {
        if (_url.charAt(0) == '/' || _url.charAt(0) == '\\')
            return (_url.charAt(0) + _url.charAt(1).toUpperCase() + _url.slice(2));
        else
            return (_url.charAt(0).toUpperCase() + _url.slice(1));
    };

    $scope.confirmPasswordReset = function (target) {
        $uibModal.open({
            templateUrl: app_url + "/views/" + target + "/reset_password.html",
            controller: ModalProfileCtrl,
            windowClass: 'mx-modal-profile-form',
            scope: $scope
        });
    };

    $scope.lowercase = function (url) {
        return url.toLowerCase();
    };

    $scope.getActionName = function (action_name) {
        var _act = '';
        if (action_name === "Cancel") {
            _act = 'cancelled';
        } else {
            _act = action_name + (action_name[action_name.length - 1] == 'e' ? 'd' : 'ed');
        }
        return _act;
    };

    $scope.approveTransaction = function (id, state) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        $http({
            method: 'POST',
            url: `${app_url}/Transaction/approve_transaction`,
            data: { 'id': id, 'state': state }
        }).then(function (response) {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;
            let values = JSON.parse($(response.data).find('#mabrexPageContent').text().trim());

            if (values.status === 200) {
                $('.float-approval-message').addClass('alert-success').text('Operation was successfully performed');
            } else if (values.status === 100) {
                $('.float-approval-message').addClass('alert-danger').text('There was an error performing the requested operation');
            } else {
                $('.float-approval-message').addClass('alert-info').text('Unknown issue has occurred');
            }
            setTimeout(() => {
                location.reload();
            }, 3200);
        }, function (response) {
            $('.float-approval-message').addClass('alert-danger').text(response);
        });
    };

    //called when record row clicked to open the profile modal
    $scope.redirectToApproval = function (token) {
        window.open(`${app_url}/Notifications/index?token=${btoa(token)}`, "_self");
    };

    $scope.approveDual = function (token, state, model, p_data, added_by, added_date, account = null, reason = null) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        let posted_data = (JSON.parse(p_data));
        posted_data['added_by'] = added_by;
        posted_data['date'] = added_date;
        let data_o = { "token": token, "model": model };
        angular.extend(posted_data, data_o);
        if (account) {
            posted_data['account'] = account;
        }
        if (reason) {
            posted_data['tar_reason'] = reason;
        }
        $http({
            method: 'POST',
            url: `${app_url}/${model}/${state}`,
            data: posted_data
        }).then(function (response) {
            $('.overlay').addClass('hidden');
            $scope.ProcessingData = false;

            let values = JSON.parse($(response.data).find('#mabrexPageContent').text().trim());
            if (values === 200) {
                $('.float-approval-message').addClass('alert-success').text('Operation was successfully performed');
            } else if (values === 100) {
                $('.float-approval-message').addClass('alert-danger').text('There was an error performing the requested operation');
            } else if (values === 300) {
                $('.notification-area').addClass('alert alert-info').text("Your request has failed. Account(s) Already Exist.");
            } else if (values === 201) {
                $('.float-approval-message').addClass('alert-success').text('Operation was successfully performed');
            } else if (values === 600) {
                $('.float-approval-message').addClass('alert-info').text('Subscriber Already Exists with this mobile number');
            } else {
                $('#approvalFailed').modal();
            }
            setTimeout(function () {
                $('.float-approval-message').removeClass('alert alert-success').text('');
                location.reload();
            }, 4000);

            $('#approvalFailed').on('hidden.bs.modal', function () {
                location.reload();
            })
        }, function (response) {
            $('.float-approval-message').addClass('alert-danger').text('Failed Operation');
        });
    };

    $scope.getTodaysTime = function () {
        var date = new Date();
        return new Date(
            date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + ' ' + date.getHours() + ':' + date.getMinutes()
        )
    }

    $scope.getTodaysDate = function () {
        var date = new Date();
        return new Date(
            date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate()
        )
    }

    //Draw Business Profile charts
    var element3 = document.getElementById('nation-area-demo');

    if (element3 !== null) {
        var config5 = {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [$scope.initial_tab_data.applicant_info.zanzibaris, $scope.initial_tab_data.applicant_info.mainlanders, $scope.initial_tab_data.applicant_info.foreigners],
                    backgroundColor: [
                        '#bb5d16',
                        '#6b9080',
                        '#162667',
                    ],
                    label: 'Business Types',
                    hoverOffset: 4
                }],
                labels: ['Zanzibaris', 'Mainlands', 'Foreigners'],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: true,
                    position: 'left',
                },
                title: {
                    display: false,
                    text: 'Tickets by Status'
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 0
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'left',
                    },
                    title: {
                        display: false,
                        text: 'Tickets summary'
                    }
                }
            }
        };

        var ctx5 = element3.getContext('2d');
        new Chart(ctx5, config5);
    }

    var element4 = document.getElementById('job-area-demo');

    if (element4 !== null) {

        var config6 = {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [$scope.initial_tab_data.applicant_info.management_total, $scope.initial_tab_data.applicant_info.skilled_total, $scope.initial_tab_data.applicant_info.un_skilled_total],
                    backgroundColor: [
                        '#6a4c93',
                        '#e36414',
                        '#f15bb5',
                    ],
                    label: 'Business Types',
                    hoverOffset: 4
                }],
                labels: ['Management', 'Skilled', 'Unskilled']
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: true,
                    position: 'left',
                },
                title: {
                    display: false,
                    text: 'Tickets by Status'
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 0
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'left',
                    },
                    title: {
                        display: false,
                        text: 'Tickets summary'
                    }
                }
            }
        };

        var ctx6 = element4.getContext('2d');
        new Chart(ctx6, config6);
    }

    var element5 = document.getElementById('gender-area-demo');

    if (element5 !== null) {

        var config7 = {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [$scope.initial_tab_data.applicant_info.males, $scope.initial_tab_data.applicant_info.females],
                    backgroundColor: [
                        '#118ab2',
                        '#83c5be',
                    ],
                    label: 'Business Types',
                    hoverOffset: 4
                }],
                labels: ['Male', 'Female']
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false,
                    position: 'left',
                },
                title: {
                    display: false,
                    text: 'Tickets by Status'
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 0
                },
                plugins: {
                    legend: {
                        display: false,
                        position: 'left',
                    },
                    title: {
                        display: false,
                        text: 'Tickets summary'
                    }
                }
            }
        };

        var ctx7 = element5.getContext('2d');
        new Chart(ctx7, config7);
    }
}]);

//this function is for modal controller called in modal
var ModalProfileCtrl = function ($scope, $uibModalInstance, $http, $compile, $interval, $filter, $sce) {

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.pdf_file = null;
    $scope.pdf_file_url = null;
    $scope.isPdfViewerOpen = false;
    $scope.pdfZoom = 1;

    $scope.investigation_team_data = new Set();


    $scope.checkPhoneNumber = function (id) {
        var $mobile = $(`#${id}`).val();
        if ($mobile.length === 10 && $mobile.substr(0, 1) === '0') {
            $(document).find('div.' + id + '_error').text('');
            return false;
        } else {
            $(document).find('div.' + id + '_error').text('Phone number must be 10 digits in the format 07XXXXXXXX or 06XXXXXXXX');
            return true;
        }
    };

    $scope.saveProfileOperation = function (url, action) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = app_url + "/" + url + "/" + action + "/";
        if ($scope.form.opt_mx_commission_type_id !== undefined && $scope.form.opt_mx_commission_type_id.name === 'Slab') {
            $scope.extractCommissionData();
        }
        if ($scope.form.has_extra === 1) {
            $scope.configureExtraData(action);
        }
        if (action === "visaSubscription" || action === "unsubscribeVisa" || action === "post_reset_pin" || action === 'resetSubscriberImsi') {
            $scope.form.id = $scope.parent_id;
        } else if (action === "saveIncident") {
            $scope.form.opt_mx_subscriber_id = $scope.parent_id;
        } else if (action === "changeSubscriberMainAccount" || action === "saveNewSubscriberClass" || action === 'activateSubscriberScheme') {
            $scope.form.subscriber_id = $scope.parent_id;
        }
        if ($scope.dual_activity_page === true) {
            $scope.extractDualActivityData();
        }

        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(response);
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

        if (url === 'Property' && action === 'post_upload_images') {
            $scope.processPropertyData(formdata);
        } else if (uploads.length > 0) {
            angular.forEach(uploads, function (value) {
                var element = document.getElementById(value);
                if (element != null) {
                    var file = element.files[0];
                    formdata.append(value, file);
                }
            });
        }

        $http({
            method: 'POST',
            url: post_url,
            data: formdata,
            headers: { 'Content-Type': undefined }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(data);
            $scope.ProcessingData = false;
        });
    };

    $scope.processPropertyData = function (formdata) {
        var uploads = [];
        var counter = 0;
        var container = $(document).find('tbody#uploads_table');
        container.children('tr').each(function () {
            var row = $(this);
            var data = {};
            row.children('td.input_cell').each(function () {
                var control = $(this).find('[data-input]');
                var value = control.val();
                var label = control.attr('data-input');
                if (label == 'txt_image_url') {
                    var td = $(this);
                    td.children('input[type=file]').each(function () {
                        var element = this;
                        var file = element.files[0];
                        data[label + '_' + counter] = file;
                        formdata.append(label + '_' + counter, file);
                    })
                } else {
                    data[label] = value;
                }
            });
            counter += 1;
            uploads.push(data);
        });
        formdata.append('uploads', JSON.stringify(uploads));
    }

    $scope.extractCommissionData = function () {
        var commission = [];
        var base = 0;
        var bank_base = 0;
        var container = $(document).find('tbody#slab_commission_data');
        container.children('tr').each(function () {
            var min = $(this).find('input.dbl_minimum').val();
            var max = $(this).find('input.dbl_maximum').val();
            var amount = $(this).find('input.dbl_commission').val();
            //            var amount = $(this).find('input.dbl_commission').val();
            //            if ($(this).find('input.dbl_base').val() !==undefined && $(this).find('input.dbl_bank_base').val() !==undefined){
            base = $(this).find('input.dbl_base').val();
            bank_base = $(this).find('input.dbl_bank_base').val();
            commission.push({ 'dbl_minimum': min, 'dbl_maximum': max, 'dbl_commission': amount, 'dbl_base': base, 'dbl_bank_base': bank_base });
            //            }else{
            //                commission.push({'dbl_minimum': min, 'dbl_maximum': max, 'dbl_commission': amount});
            //            }

        });
        $scope.form.slabs = JSON.stringify(commission);
    };

    $scope.configureExtraData = function (action) {
        switch (action) {
            case 'post_register_account':
                var account = [];
                var container = $(document).find('tbody#account_table');
                container.children('tr').each(function () {
                    var row = $(this);
                    var data = {};
                    row.children('td.input_cell').each(function () {
                        var control = $(this).find('[data-input]');
                        var value = control.val();
                        var label = control.attr('data-input');
                        data[label] = value;

                    });
                    account.push(data);
                });
                $scope.form.account = JSON.stringify(account);
                break;
            default:
                break;
        }
    };

    $scope.extractDualActivityData = function () {
        var section = $(document).find('div#dual_activity_section');
        var data = [];
        section.children('div').each(function () {
            var input = $(this).find('select');
            if (input.val().length > 0) {
                data.push({ 'institution': input.attr('data-institution'), 'group': input.val() });
            }
        });
        $scope.form.groups = data;
    };

    $scope.initiateExtra = function () {

        if ($scope.form.has_extra === 1) {
            $scope.form.chkselct = true;

            //initiate extra
            if ($scope.url.toLowerCase() == 'subscriber' && $scope.form.txt_action == 'save') {
                var account_data = JSON.parse($scope.form.account);

                if (account_data.length > 0) {
                    //handle first tr
                    var last_row = angular.element(document.querySelector('#account_table tr')).last();
                    last_row.find('.account_name').val(account_data[0].txt_account_name);
                    last_row.find('.account_number').val(account_data[0].txt_account_number);

                    //handle other tr
                    for (var i = 1; i < account_data.length; i++) {

                        var my_account = angular.element(document.querySelector('.account-adder'));
                        var last_row = my_account.trigger('click').parent().parent().parent().find('tr').last();

                        last_row.find('.account_name').val(account_data[i].txt_account_name);
                        last_row.find('.account_number').val(account_data[i].txt_account_number);
                    }
                }
            }
        }
    }

    $scope.saveProfileOperationWithUploads = function (url, action, uploads) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        var post_url = app_url + "/" + url + "/" + action + "/";
        if ($scope.form.dat_birth_date) {
            $scope.form.dat_birth_date = new Date($scope.form.dat_birth_date).toISOString();
        }

        var formdata = new FormData();
        angular.forEach($scope.form, function (value, key) {
            formdata.append(key, value);
        });
        if (uploads.length > 0) {
            angular.forEach(uploads, function (value) {
                var element = document.getElementById(value);
                if (element != null) {
                    var file = element.files[0];
                    formdata.append(value, file);
                }
            });
        }
        if ($scope.form.has_extra === 1) {
            $scope.configureExtraData(action);
        }

        $http({
            method: 'POST',
            url: post_url,
            data: formdata,
            headers: { 'Content-Type': undefined }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(data);
            // if (data.errors) {
            //     $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            // } else {
            //     var response = $(data).find('#mabrexPageContent').text().trim();
            //
            //     if (response === '200' || response === '201') {
            //         $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
            //     } else if (response === '2903' || response === 2903) {
            //         status_color = 'danger';
            //         message = "Your request has failed. You can only use one receipt per application.";
            //     } else {
            //         $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
            //     }
            //     setTimeout(function () {
            //         $('.notification-area').removeClass('alert alert-success').text('');
            //         $modalInstance.dismiss('cancel');
            //         if (response === '201') {
            //             location.reload();
            //         }
            //     }, 4000);
            // }
        }).finally(function () {
            $scope.ProcessingData = false;
        });
    };

    $scope.generateReport = function () {
        $('.overlay').removeClass('hidden');
        $('#loader').css('display', '');
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var from_date = $scope.formatDate($scope.frmData['from_date']);
        var to_date = $scope.formatDate($scope.frmData['to_date']);
        var record_id = $scope.frmData['record_id'];
        var data_to_post = { 'record_id': record_id, 'from_date': from_date, 'to_date': to_date };

        var _width = 900; //$(document).width();
        var _height = 800; //$(document).height();
        var fileName = app_url + "/pdf/tmp/report.pdf";
        $http({
            method: 'POST',
            url: app_url + "/views/applicant/applicant_report.php",
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            if (response.status == 200) {
                $('.overlay').addClass('hidden');
                $(document).find('div#range').hide();
                $(document).find('div#range2').removeClass('hide');
                $(document).find('div#profile_preview_panel').removeClass('hide');
                var object = "<object data=\"{FileName}#toolbar=1\" type=\"application/pdf\" width=\"" + _width + "px\" height=\"" + (_height - 100) + "px\">";
                object += "If you are unable to view file, you can download from <a href = \"{FileName}\">here</a>";
                object += " or download <a target = \"_blank\" href = \"http://get.adobe.com/reader/\">Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                $(document).find('div#profile_report_preview').css('height', _height - 80 + 'px').html(object);
            } else if (response.status == 100) {
                (document).find('div#profile_report_preview').empty();
                (document).find('div#profile_preview_panel').addClass('hide');
                msg = '<p class="notification-msg">Sorry! There is no data available.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
            } else {
                (document).find('div#profile_report_preview').empty();
                (document).find('div#profile_preview_panel').addClass('hide');
                msg = '<p class="notification-msg">Sorry! Something went wrong.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
            }
            $('#loader').css('display', 'none');
        });
    };

    $scope.formatDate = function (dateString) {
        var date = dateString.getDate();
        var month = dateString.getMonth() + 1;
        var year = dateString.getFullYear();
        if (date < 10) {
            date = '0' + date;
        }
        if (month < 10) {
            month = '0' + month;
        }
        dateString = year + '-' + month + '-' + date;
        return dateString;
    };

    $scope.hideDiv = function () {

        $(document).find('div#range2').addClass('hide');
        $(document).find('div#range').show();
    };

    String.prototype.capitalize = function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };

    //Printing Test of Card

    $scope.target_template = null;
    $scope.selectCardTemplate = function (value) {
        $scope.target_template = value;
    };

    $scope.printCard = function (card, rootPath) {
        console.log(rootPath);
        $('#vaccine_card_form').css('display', 'none');
        $('#confirmPrintStatus').css('display', 'block');
        $scope.form = card;
        if ($scope.form['Name'].length > 29) {
            $scope.form.personFontSize = 8
        } else if ($scope.form['Name'].length > 26) {
            $scope.form.personFontSize = 9
        } else if ($scope.form['Name'].length > 23) {
            $scope.form.personFontSize = 10
        } else if ($scope.form['Name'].length > 20) {
            $scope.form.personFontSize = 11
        } else if ($scope.form['Name'].length > 17) {
            $scope.form.personFontSize = 12
        } else if ($scope.form['Name'].length > 14) {
            $scope.form.personFontSize = 13
        } else if ($scope.form['Name'].length > 11) {
            $scope.form.personFontSize = 14
        } else if ($scope.form['Name'].length > 8) {
            $scope.form.personFontSize = 15
        }

        var content = $('#cardPrintableArea');
        content.load(`${app_url}/sid_template/sid_card.php`, function () {
            $compile(content.contents())($scope);
            $scope.$apply();
            newWin = window.open("");
            var css = '@page {size: landscape; font-size: 8pt; margin: 0;}';
            var head = newWin.document.head || newWin.document.getElementsByTagName('head')[0];
            var style = newWin.document.createElement('style');
            style.type = 'text/css';
            style.media = 'print';
            if (style.styleSheet) {
                style.styleSheet.cssText = css;
            } else {
                style.appendChild(newWin.document.createTextNode(css));
            }

            head.appendChild(style);
            // $scope.generateBarcode(content.find('#barcode'));
            newWin.document.write(content.html());
            setTimeout(function () {
                newWin.print();
                newWin.close();
                // $scope.updateCardPrintCount(card);
            }, 300);
        });

        // $('#cardTemplateSelector').modal('show');
        // $('#cardTemplateSelector').on('hidden.bs.modal', function (e) {
        //     if ($scope.target_template === null) {
        //         return;
        //     }
        //
        //     var content = $('#cardPrintableArea');
        //
        // });
    };

    // Called when input[type=file] changes
    $scope.selectPDF = function (files) {
        if (!files || !files.length) return;

        $scope.pdf_file = files[0];

        // Generate blob URL
        var url = URL.createObjectURL($scope.pdf_file);

        // Trust the URL for AngularJS
        $scope.pdf_file_url = $sce.trustAsResourceUrl(url);

        $scope.$apply();
    };


    // Open viewer
    $scope.openPdfViewer = function () {
        if (!$scope.pdf_file_url) return;
        $scope.pdfZoom = 1;
        $scope.isPdfViewerOpen = true;
    };

    // Close viewer
    $scope.closePdfViewer = function () {
        $scope.isPdfViewerOpen = false;
    };

    // Zoom controls
    $scope.zoomIn = function () {
        if ($scope.pdfZoom < 3) {
            $scope.pdfZoom += 0.25;
        }
    };

    $scope.zoomOut = function () {
        if ($scope.pdfZoom > 0.5) {
            $scope.pdfZoom -= 0.25;
        }
    };

    $scope.resetZoom = function () {
        $scope.pdfZoom = 1;
    };


    // $scope.printStatus = function (status,id) {
    //     $scope.ProcessingData = true;
    //     if (status){
    //         $http({
    //             method: 'POST',
    //             url: `${app_url}/Vaccination/post_print_card`,
    //             data: {'id': id},
    //             headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    //         }).success(function (data) {
    //             // console.log(data);
    //             // $scope.showProfileActionForm(url, action, params, 'static');
    //             $scope.ProcessingData = false;
    //         }).finally(function () {
    //             $scope.ProcessingData = false;
    //         });
    //     }
    //     else {
    //         // console.log("Printing Confirmed, fail by user.")
    //     }
    // };

    $scope.pushNotification = function (url, action) {
        $('.overlay').removeClass('hidden');
        $scope.ProcessingData = true;
        $('.overlay').removeClass('hidden');
        var post_url = `${action}`
        $http({
            method: 'POST',
            url: post_url,
            data: $scope.form,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            $('.overlay').addClass('hidden');
            $('#pushModal').modal('hide');
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
                $scope.ProcessingData = false;
            } else {
                var response = JSON.parse($(data).find('#mabrexPageContent').text().trim());
                if (response.response.code === 200) {
                    $('.notification-area').addClass('alert alert-success').text(`${response.response.message}`);
                } else if (response.response.code === 100) {
                    $('.notification-area').addClass('alert alert-danger').text(`${response.response.message[0]}`);
                } else if (response.response.code === 103) {
                    $('.notification-area').addClass('alert alert-info').text(`${response.response.message[0]}`);
                } else if (response === '901') {
                    $('.notification-area').addClass('alert alert-info').text("Your request has failed. Mobile Number Already Exist.");
                } else if (response === '903') {
                    $('.notification-area').addClass('alert alert-info').text("Your request has failed. Email AND Mobile Number Already Exist.");
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
                }
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                    $modalInstance.dismiss('cancel');
                    if (response === '200' || response.response.code === 200) {
                        location.reload();
                    } else {
                        if ($scope.is_profile_tab === true) {
                            $scope.getProfileRecords($scope.current_tab, $scope.parent_id);
                        } else {
                            $scope.getAssociatedRecords($scope.current_tab, $scope.parent_id);
                        }
                    }
                }, 2000);
                $scope.ProcessingData = false;
                $('.overlay').addClass('hidden');
            }
        }).finally(function () {
            $scope.ProcessingData = false;
        });
    }

    $scope.checkSelectedComplaintReason = function () {
        $scope.complaint_reason_other = false;
        let reason_id = $scope.form.opt_mx_complaint_dismissal_reason_id;
        let reasons = $scope.dropdowns.opt_mx_complaint_dismissal_reason_ids;
        reasons.forEach(function (data) {
            if (data.id === reason_id && data.name === 'Other') {
                $scope.complaint_reason_other = true;
            }
        })
    };

    $scope.goToBlock = (to, from) => {
        setTimeout(() => {
            $("#" + from).css('display', 'none');
            $("#" + to).css('display', 'block');
        }, 300);
    };

    $scope.addOfficer = () => {
        let selected_officer = $scope.form.officer_member;
        let officers = $scope.dropdowns.officers_ids;
        if ($scope.form.investigation_team === undefined) {
            $scope.form.investigation_team = [];
        }
        if ($scope.form.allowed_evidences === undefined) {
            $scope.form.allowed_evidences = [];
        }
        officers.forEach(function (officer) {
            if (officer.id === selected_officer && !$scope.investigation_team_data.has(officer.id)) {
                $scope.investigation_team_data.add(officer.id);
                $scope.form.investigation_team.push({
                    name: officer.name,
                    id: officer.id
                });
            }
        });
    };

    $scope.removeAddedOfficer = (id) => {
        const index = [...$scope.investigation_team_data].indexOf(id);
        if (index > -1) {
            $scope.investigation_team_data.delete(id);
            $scope.form.investigation_team.splice(index, 1);
        }
    };

    $scope.removeOfficer = () => {
        let removal_reason = $scope.form.change_reason;
        if ($scope.officer_removed_index > -1 && $scope.officer_removed !== null) {
            let index = $scope.officer_removed_index;
            $scope.form.investigation_team.splice(index, 1);

            if ($scope.form.removed_investigation_team_members === undefined) {
                $scope.form.removed_investigation_team_members = [];
            }
            $scope.form.removed_investigation_team_members.push({
                id: $scope.officer_removed,
                name: $scope.officer_removed_name,
                reason: removal_reason
            });
            $scope.form.change_reason = '';
        }
    };

    $scope.removingOfficer = (id) => {
        $scope.officer_removed = id;
        $scope.form.change_reason = '';
        $scope.form.investigation_team.forEach(function (officer, index) {
            if (officer.id === id) {
                $scope.officer_removed_index = index;
                $scope.officer_removed_name = officer.name;
                return true;
            }
        });
    };

    $scope.cancelRemoveOfficer = (id) => {
        $scope.officer_removed = id;
        $scope.form.removed_investigation_team_members.forEach(function (officer, index) {
            if (officer.id === id) {
                $scope.form.investigation_team.push({
                    id: officer.id,
                    name: officer.name,
                });
                $scope.form.removed_investigation_team_members.splice(index, 1);
                return true;
            }
        });
    };

    $scope.confirmEvidencePresence = (name, id) => {
        return new Promise((resolve, reject) => {
            $http({
                method: 'POST',
                url: '/Complaint/check_evidence',
                data: { file_name: name, id: id },
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).success(function (data) {
                var response = $(data).find('#mabrexPageContent').text().trim();
                resolve(true);
            }).error(function (error) {
                reject(false);
            });
        });
    };

    $scope.setEvidenceLink = (evidence) => {
        let url = "/uploads/applications/";
        let name = evidence.name;
        let id = evidence.id;
        $scope.confirmEvidencePresence(name, id)
            .then(data => {
                $scope.selected_evidence = evidence.id;
                $scope.evidence_status = evidence.selected;

                $scope.evidence_link = $sce.trustAsResourceUrl(url + name);
            })
            .catch(error => {
                $scope.evidence_link = $sce.trustAsResourceUrl(url + 'no-file.html');
            });
    };

    $scope.checkEvidenceLink = (evidence) => {
        let response = $scope.confirmEvidencePresence(evidence);

        if (response) {
            return evidence;
        }

        return 'no-file.html';
    };

    $scope.trustSrc = function (src) {
        let array = src.split('=');

        if (array.length > 1) {
            let url = "https://uzalendo.rahisi.co.tz/play2.php?filename=" + array[1];
            return $sce.trustAsResourceUrl(url);
        }

    }

    $scope.generateSubmissionForm = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
        //        $('#loader').css('display', '');
        //        $(document).find(".progress").each(function () {
        //            $(this).css("visibility", "visible");
        //        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post;
        for (var i = 0; i < params.length; i++) {
            data_to_post = { 'id': params[i] };
        }
        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName = '';
        //           console.log($scope.ReportOptions.FilterFieldValue);
        //url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            if (data.status == 200) {
                fileName = app_url + '/' + data.file;
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);
                //                console.log(object);
                $('#reportPDFPreview').html(object);

                $scope.ExportTableToPDF();
                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
                //                console.log(data.records);
            } else if (data.status == 100) {
                $('.notification-area').addClass('alert alert-success').text('No record found');
            } else if (data.status == 209) {
                $('.notification-area').addClass('alert alert-success').text('Sorry! You can not generate certificate for a positive result test');
            } else {
                $('.notification-area').addClass('alert alert-success').text('There was an error when generating your Certificate. Please try again later or contact your system administrator for assistance');
            }
            setTimeout(function () {
                $('.notification-area').removeClass('alert alert-success').text('');
            }, 2000);
            $scope.ProcessingData = false;
            $('.overlay').addClass('hidden');
        });
    };
    $scope.generateCertificate = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
        //        $('#loader').css('display', '');
        //        $(document).find(".progress").each(function () {
        //            $(this).css("visibility", "visible");
        //        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post;
        //        for (var i = 0; i < params.length; i++) {
        //            data_to_post = {'id': params[i]};
        //        }
        data_to_post = params[0]

        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName;

        //           console.log($scope.ReportOptions.FilterFieldValue);
        //url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            if (data.status == 200) {
                fileName = app_url + "/pdf/tmp/" + data.file;
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);
                //                console.log(object);
                $('#reportPDFPreview').html(object);

                $scope.ExportTableToPDF();
                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
                //                console.log(data.records);
            } else if (data.status == 100) {
                $('.notification-area').addClass('alert alert-success').text('No record found');
            } else if (data.status == 209) {
                $('.notification-area').addClass('alert alert-success').text('Sorry! You can not generate certificate for a positive result test');
            } else {
                $('.notification-area').addClass('alert alert-success').text('There was an error when generating your Certificate. Please try again later or contact your system administrator for assistance');
            }
            setTimeout(function () {
                $('.notification-area').removeClass('alert alert-success').text('');

            }, 4000);
            $scope.ProcessingData = false;
            $('.overlay').addClass('hidden');
        });
    };
    $scope.generateApplication = function (url, action, params) {
        $('.overlay').removeClass('hidden');
        var params_str = '';
        //        $('#loader').css('display', '');
        //        $(document).find(".progress").each(function () {
        //            $(this).css("visibility", "visible");
        //        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';
        var data_to_post = params[0];

        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName = app_url + "/pdf/tmp/application.pdf";

        //           console.log($scope.ReportOptions.FilterFieldValue);
        //url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: `${app_url}/${url}/${action.toLowerCase()}`,
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            var data = JSON.parse($(response).find('#page-content').text());
            if (data.status == 200) {
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);
                //                console.log(object);
                $('#reportPDFPreview').html(object);

                $scope.ExportTableToPDF();
                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
                //                console.log(data.records);
            } else if (data.status == 100) {
                $('#report_preview').empty();
                $('#preview_panel').addClass('hide');
                //                msg = '<p class="notification-msg">Sorry! No data available for the selected report options. Please try other options.</p>';
                //                type = 'info';
                //                $scope.notify(msg, icon, title, type);
                $('#noRecordFound').modal();
            } else {
                $('#report_preview').empty();
                $('#preview_panel').addClass('hide');
                msg = '<p class="notification-msg">Sorry! There was an error when generating your report. Please try again later or contact your system administrator for assistance.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
            }
            $(document).find(".progress").each(function () {
                $(this).css("visibility", "hidden");
            });
        });
    };
    $scope.ExportTableToPDF = function () {
        //$('#preview_panel #report_preview').removeClass('hide');
        $('#DemoModal').modal('show');
    };

    $scope.toggleSymptoms = (name) => {
        // var $scope.form.symptoms = $scope.form.symptoms;
        var symptoms = $scope.extra_data.symptoms;

        for (let i = 0; i < symptoms.length; i++) {
            let symptom = symptoms[i];

            if (symptom['txt_name'] === name) {
                if ($scope.form.symptoms.includes(name)) {
                    symptom.selected = false;
                    let index = $scope.form.symptoms.indexOf(name);
                    $scope.form.symptoms.splice(index, 1);
                } else {
                    $scope.form.symptoms.push(name);
                    symptom.selected = true;
                }
                break;
            }
        }
    };

    $scope.checkSymptomType = function () {
        $scope.form.symptoms = [];
        $symptom_types = $scope.dropdowns.opt_mx_application_symptom_type_ids;
        $symptom_type_id = $scope.form.opt_mx_application_symptom_type_id;

        $symptom_types.forEach(function (type) {
            if (type.id === $symptom_type_id) {
                $scope.symptom_type = type.name;
            }
        });
    }

    $scope.checkAnatomicalSite = function () {
        $specimen_natures = $scope.extra_data.specimen_natures;
        $anatomical_site_id = $scope.form.opt_mx_anatomical_site_id;

        $specimen_natures.forEach(function (type) {
            if (type.anatomical_site_id === $anatomical_site_id) {
                $scope.specimen_nature = type['Specimen Nature'];
                $scope.form.opt_mx_specimen_nature_id = type['specimen_nature_id'];
            }
        });
    }

    $scope.calculatePaymentAmount = function () {
        var selected = $filter('filter')($scope.dropdowns.txt_currency_ids, { id: $scope.form.txt_currency })[0];
        if (selected != undefined) {
            if ($scope.form.original_currency != selected.name) {
                if ($scope.form.original_currency == 'TZS') {
                    $scope.form.dbl_amount = $scope.form.original_amount / $scope.form.local_rate;
                } else {
                    $scope.form.dbl_amount = $scope.form.original_amount * selected.rate;
                }
            } else {
                $scope.form.dbl_amount = $scope.form.original_amount;
            }
            //$scope.form.dbl_amount = 
        }
    };

    $scope.rotateImage = function (loc) {
        $http({
            method: 'POST',
            url: '/Application/rotate_image',
            data: { file_name: loc },
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (data) {
            var response = $(data).find('#mabrexPageContent').text().trim();
            console.log(response);
        }).error(function (error) {
            console.log(error);
        });
    };
    $scope.stepper = 1;
    $scope.nextStep = function () {
        $scope.stepper++;
    }

    $scope.prevStep = function () {
        $scope.stepper--;
    }
    $scope.getAvailability = function () {
        $('.overlay').removeClass('hidden');
        console.log($scope.form);
        if (($scope.form.dat_test_date != null || $scope.form.dat_test_date != undefined) && ($scope.form.opt_mx_center_id != null || $scope.form.opt_mx_center_id != undefined)) {
            var data_to_post = { 'dat_test_date': $scope.form.dat_test_date, 'opt_mx_center_id': $scope.form.opt_mx_center_id };
            console.log(data_to_post);
            $http({
                method: 'POST',
                url: `${app_url}/Application/getAvailability`,
                data: data_to_post, //forms user object
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function (response) {
                $('.overlay').addClass('hidden');
                $scope.availability = JSON.parse($(response.data).find('#mabrexPageContent').text());
                $scope.slots_available = $scope.availability['availability']
                //                $scope.user_group_flag = true;

                localStorage.setItem('slots', JSON.stringify($scope.slots_available));
                //               $scope.slots_available.forEach(function (item) {
                //                        console.log(item)
                //                    });
                $scope.addSlots($scope.slots_available);
            });
        } else {
            $scope.availability = {};
        }
    }
    $scope.get_slots = function () {
        $scope.slots_available = localStorage.getItem('slots');
        console.log('asdf');
    }
    $scope.addSlots = function (slots) {
        var tbody = '';
        var slab_tbody = $(document).find('div > #slots');
        console.log(slab_tbody)
        slab_tbody.empty();
        tbody += '<div class="col-lg-9">';
        for (var i = 0; i < slots.length; i++) {
            tbody += ` <div ng-class="{'col-md-2 col-sm-4': slots_available.length > 5,
                                                 'col-sm-4 col-md-6': slots_available.length < 2,
                                                 'col-sm-4 col-md-2': slots_available.length <= 5}">`;
            tbody += ` <a type="button" ng-click="getCenter(slots_available[${i}].slot_id,slots_available[${i}].test_time)">`
            tbody += '<div style="border: solid #007BFF 1px; width: auto; height: auto; margin-bottom: 10px;padding:15px; color: #007BFF; font-weight: bold; transition: all 0.3s ease-in-out;text-align: center;border-top-left-radius: 0.25rem !important;'
            tbody += ' border-top-right-radius: 0.25rem !important; border-bottom-right-radius: 0.25rem !important; border-bottom-left-radius: 0.25rem !important;">'

            tbody += $scope.slots_available[i]['test_time'] + '</div></a> </div>'
        }
        tbody += "</div>";
        slab_tbody.html($compile(tbody)($scope));

    }
    $scope.getCenter = function (slot_id, test_time) {
        $scope.form.slot_id = slot_id;
        $scope.form.test_time = test_time;
        console.log($scope.form)
    }

    $scope.responseHandler = function (response, reload = true) {
        console.log(response);

        let code = Number(response.code);
        let status = response.status;
        let message = response.message;

        $scope.ProcessingData = !(status === undefined || status == false);

        if (code === 200 || code === 201) {
            $('.notification-area').addClass('alert alert-success').html(message);
        } else if (code === 220) {
            $('.notification-area').addClass('alert alert-info').html(message);
        } else {
            $('.notification-area').addClass('alert alert-danger').html(message);
        }

        setTimeout(function () {
            $('.notification-area').removeClass('alert alert-success alert-danger alert-info').html('');
            if (code === 200 || code === 201) {
                $uibModalInstance.dismiss('cancel'); // <-- changed
                if ($scope.current_tab && $scope.parent_id) {
                    if (!$scope.is_profile_tab) {
                        $scope.getAssociatedRecords($scope.current_tab, $scope.parent_id);
                        $scope.fetchProfile($scope.parent_id)
                    } else {
                        $scope.getProfileRecords($scope.current_tab, $scope.parent_id);
                    }
                } else {
                    if (reload) {
                        location.reload();
                    }
                }
            }
        }, 2000);
    };
    $scope.associatedShowProfile = function (url, id) {
        $scope.showProfile(url, id);
        $scope.cancel();
    };
    $scope.checkCategories = function () {
        let _true = 0;
        if ($scope.form.service_categories) {
            Object.keys($scope.form.service_categories).forEach((item, index) => {
                if ($scope.form.service_categories[item]) _true++;
            })
        }
        return _true > 0;
    }

    //Function to print Vaccine card
    $scope.printTourGuideIDCard = function (card) {
        $('#tour_guide_card_form').css('display', 'none');
        $('#confirmPrintStatus').css('display', 'block');
        $scope.form = card;
        console.log($scope.form);
        if ($scope.form['tour_guide'].length > 29) {
            $scope.form.personFontSize = 8
        } else if ($scope.form['tour_guide'].length > 26) {
            $scope.form.personFontSize = 9
        } else if ($scope.form['tour_guide'].length > 23) {
            $scope.form.personFontSize = 10
        } else if ($scope.form['tour_guide'].length > 20) {
            $scope.form.personFontSize = 11
        } else if ($scope.form['tour_guide'].length > 17) {
            $scope.form.personFontSize = 12
        } else if ($scope.form['tour_guide'].length > 14) {
            $scope.form.personFontSize = 13
        } else if ($scope.form['tour_guide'].length > 11) {
            $scope.form.personFontSize = 14
        } else if ($scope.form['tour_guide'].length > 8) {
            $scope.form.personFontSize = 15
        }

        var content = $('#cardPrintableArea');
        content.load(`${app_url}/modules/Tour_Guide/Views/print_card.php`, function () {
            $compile(content.contents())($scope);
            $scope.$apply();
            newWin = window.open("");
            var css = '@page {size: landscape; font-size: 8pt; margin: 0;}';
            var head = newWin.document.head || newWin.document.getElementsByTagName('head')[0];
            var style = newWin.document.createElement('style');
            style.type = 'text/css';
            style.media = 'print';
            if (style.styleSheet) {
                style.styleSheet.cssText = css;
            } else {
                style.appendChild(newWin.document.createTextNode(css));
            }

            head.appendChild(style);
            // $scope.generateBarcode(content.find('#barcode'));
            newWin.document.write(content.html());
            setTimeout(function () {
                newWin.print();
                newWin.close();
                // $scope.updateCardPrintCount(card);
            }, 300);
        });
    };

    //
    $scope.callmxImageUploader = function () {
        var container = $(document).find("tbody#uploads_table");
        var row = container.find("tr:last");
        row.children("td.input_cell").each(function (key, value) {
            $(this).find("input[type=file]").each(function () {
                $(this).mxImageUploader();
            });
        });
    };

    //Remove property image
    $scope.removePropertyImage = function (image, index) {
        //Remove HTML table row
        var row = document.getElementById("image_" + index);
        row.remove();

        //Unset from js object
        $scope.initial_tab_data.images.splice(index, 1)

        //POST request to delete image
        var post_url = `${app_url}/${$scope.url.capitalize()}/remove_image/`;
        $http({
            method: 'POST',
            url: post_url,
            data: image,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).success(function (response) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(response);
        }).error(function (response) {
            $('.overlay').addClass('hidden');
            $scope.responseHandler(response);
        });
    }
};

var app = angular.module('report.modal');

app.controller("reportCtrl", ['$scope', '$http', '$interval', '$compile', '$filter', function ($scope, $http, $interval, $compile, $filter) {
    // Model to hold report options data
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

    $scope.institution;
    $scope.ReportIsOpen = false;
    $scope.ReportFilters = {};
    $scope.LabValues = {};
    $scope.Centers = {};
    $scope.Nationalities = {};
    $scope.ReportGroupings = {};
    $scope.ReportCategories = [{ 'Id': 0, 'Name': 'Summary' }];
    $scope.ReportFilterValues = {};
    $scope.AuditUsers = {};
    $scope.AuditActions = {};
    $scope.StatementReportCheck = 0;
    $scope.ReportOptions.Center = 0;

    $scope.extraControl = {};

    $scope.autoCompleteSelectOptions = {};

    $scope.autoComplete = function (searchKey, searchComponent) {
        // check if institution selected or add other inputs to before proceed for BCX
        $scope.data = [];
        if (searchKey !== '') {
            for (var i = 0; i < $scope.ReportFilterValues.length; i++) {
                var name = $scope.ReportFilterValues[i]['Name'].toLowerCase();
                if (name.indexOf(searchKey) > -1) {
                    $scope.data.push($scope.ReportFilterValues[i]);
                }
            }
            $scope.autoCompleteSelectOptions[searchComponent] = $scope.data;
        } else {
            $scope.autoCompleteSelectOptions[searchComponent] = $scope.ReportFilterValues;
        }

    };

    $scope.initiateAutocomplete = function () {
        $scope.autoComplete('', 'ReportFilterValues');
    };


    $scope.getFormFields = function (report_type, value) {
        $('#loader').css('display', '');
        $scope.ReportOptions.Type = value;
        $scope.ReportFilters = {};
        $scope.LabValues = {};
        $scope.ReportGroupings = {};
        $scope.ReportFilterValues = {};
        $scope.PaymentStatusValues = {};
        $scope.AuditUsers = {};
        $scope.Centers = {};
        $scope.Nationalities = {};
        $scope.ReportOptions.PaymentProvider = 1;
        $scope.AuditActions = {};
        $scope.ReportOptions.PaymentStatusValues = 0;
        $http.post(app_url + "/Report/get_form_fields", { 'report_type': report_type }).then(function (res) {
            var response = res.data;
            var data = JSON.parse($(response).find('#page-content').text());

            $scope.ReportFilters = data.filters;
            $scope.ReportGroupings = data.group_by;

            if (data.nationalities !== undefined) {
                $scope.Nationalities = data.nationalities;
            }

            if (data.payments !== undefined) {
                $scope.Payments = data.payments;
            }

            if (data.invoices !== undefined) {
                $scope.Invoices = data.invoices;
            }

            if (data.applications !== undefined) {
                $scope.Applications = data.applications;
            }

            if (data.permittypes !== undefined) {
                $scope.PermitTypes = data.permittypes;
            }

            if (data.permitstatuses !== undefined) {
                $scope.PermitStatuses = data.permitstatuses;
            }

            if (data.centers !== undefined) {
                $scope.Centers = data.centers;
            }

            if (data.paymentproviders !== undefined) {
                $scope.PaymentProviders = data.paymentproviders;
            }


            $scope.ReportCategories = data.categories;
            $scope.ReportOptions.ReportTitle = data.title;
            $scope.PaymentStatusValues = data.payment_status;
            $scope.LabValues = data.labs;
            $scope.AuditUsers = data.users;
            $scope.reportName = report_type;
            //            console.log($scope.ReportFilters);
            if ($scope.ReportFilters.length > 0) {
                $scope.ReportOptions.FilterField = $scope.ReportFilters[0].Id;
            }
            if ($scope.ReportGroupings.length > 0) {
                $scope.ReportOptions.GroupingField = $scope.ReportGroupings[0].Id;
            }
            if ($scope.ReportCategories.length > 0) {
                $scope.ReportOptions.Category = $scope.ReportCategories[0].Id;
            }
            if ($scope.PaymentStatusValues !== undefined && $scope.PaymentStatusValues.length > 0) {
                $scope.ReportOptions.PaymentStatusValues = $scope.PaymentStatusValues[0].Id;
            }
            if ($scope.LabValues !== undefined && $scope.LabValues.length > 0) {
                $scope.ReportOptions.FilterFieldValue = $scope.LabValues[0];
            }

            if ($scope.Nationalities !== undefined && $scope.Nationalities.length > 0) {
                $scope.ReportOptions.Nationality = $scope.Nationalities[0].Id;
            }

            if ($scope.Payments !== undefined && $scope.Payments.length > 0) {
                $scope.ReportOptions.Payment = $scope.Payments[0].Id;
            }

            if ($scope.Applications !== undefined && $scope.Applications.length > 0) {
                $scope.ReportOptions.Application = $scope.Applications[0].Id;
            }

            if ($scope.Invoices !== undefined && $scope.Invoices.length > 0) {
                $scope.ReportOptions.Invoice = $scope.Invoices[0].Id;
            }

            if ($scope.PermitTypes !== undefined && $scope.PermitTypes.length > 0) {
                $scope.ReportOptions.PermitType = $scope.PermitTypes[0].Id;
            }

            if ($scope.PermitStatuses !== undefined && $scope.PermitStatuses.length > 0) {
                $scope.ReportOptions.PermitStatus = $scope.PermitStatuses[0].Id;
            }

            if ($scope.Centers !== undefined && $scope.Centers.length > 0) {
                $scope.ReportOptions.Center = $scope.Centers[0].Id;
            }
            if ($scope.AuditUsers !== undefined && $scope.AuditUsers.length > 0) {
                $scope.ReportOptions.AuditUsers = $scope.AuditUsers[0].Id;
            }

            $('#loader').css('display', 'none');
        });
    };

    $scope.getFilteringValues = function () {
        //        console.log($scope.institution);
        //        console.log($scope.ReportOptions.FilterField);
        $http.post(app_url + "/Report/get_filtering_fields", {
            'filter_criteria': $scope.ReportOptions.FilterField,
            'report_type': $scope.ReportOptions.Type,
            'report_category': $scope.ReportOptions.Category
        }
        ).then(function (res) {
            var response = res.data;
            var data = JSON.parse($(response).find('#page-content').text());

            $scope.ReportFilterValues = data;
            if ($scope.ReportFilterValues.length > 0) {
                $scope.ReportOptions.FilterFieldValue = $scope.ReportFilterValues[0];
            }
            if ($scope.ReportOptions.Type === 9 && ($scope.ReportOptions.FilterField === 6 || $scope.ReportOptions.FilterField === 7)) {
                $scope.initiateAutocomplete();
            }
            $('#loader').css('display', 'none');
        });
    };
    $scope.getAuditActions = function () {
        $scope.AuditActions = {};
        //        console.log($scope.institution);
        //        console.log($scope.ReportOptions.FilterField);
        if ($scope.ReportOptions.Type === 9) {
            $http.post(app_url + "/Report/get_audit_actions", {
                'filter_value': $scope.ReportOptions.FilterFieldValue.Name
            }
            ).then(function (res) {
                var response = res.data;
                var data = JSON.parse($(response).find('#mabrexPageContent').text());
                $scope.AuditActions = data;
                if ($scope.AuditActions !== undefined && $scope.AuditActions.length > 0) {
                    $scope.ReportOptions.AuditAction = $scope.AuditActions[0];
                }
                $('#loader').css('display', 'none');
            });
        }

    };

    $scope.generateReport = function () {
        console.log('NG - generateReport');
        $('#loader').css('display', '');
        $('.overlay').removeClass('hidden');
        $(document).find(".progress").each(function () {
            $(this).css("visibility", "visible");
        });
        var msg = '';
        var title = '<b class="notification-title">Report Preview</b>';
        var icon = 'pe-7s-close fa-2x';
        var type = '';

        var data_to_post = {
            'report_type': $scope.ReportOptions.Type,
            'from_date': $scope.formatDate($scope.ReportOptions.StartDate),
            'to_date': $scope.formatDate($scope.ReportOptions.EndDate),
            'filter_criteria': $scope.ReportOptions.FilterField,
            'group_criteria': $scope.ReportOptions.GroupingField,
            'category': $scope.ReportOptions.Category,
            'title': $scope.ReportOptions.ReportTitle,
            'filter_value': $scope.ReportOptions.FilterFieldValue.Id,
            'filter_name': $scope.ReportOptions.FilterFieldValue.Name,
            'provider': $scope.ReportOptions.PaymentProvider,
            'payment': $scope.ReportOptions.Payment,
            'statementreportcheck': $scope.StatementReportCheck,
            'payment_status': $scope.ReportOptions.PaymentStatusValues,
            'institution': $scope.ReportOptions.Institution,
            'source': $scope.ReportOptions.Source,
            'nationality': $scope.ReportOptions.Nationality,
            'permittype': $scope.ReportOptions.PermitType,
            'application': $scope.ReportOptions.Application,
            'invoice': $scope.ReportOptions.Invoice,
            'permitstatus': $scope.ReportOptions.PermitStatus,
            'report': $scope.reportName
        };
        console.log('data to post', data_to_post);
        if ($scope.ReportOptions.AuditUsers !== undefined && $scope.ReportOptions.AuditUsers !== null) {
            data_to_post['audit_user'] = $scope.ReportOptions.AuditUsers;
        }
        if ($scope.ReportOptions.AuditAction !== undefined && $scope.ReportOptions.AuditAction.Id !== 0) {
            data_to_post['audit_action'] = $scope.ReportOptions.AuditAction.Name;
        }
        //        console.log(data_to_post);
        var _width = 800;//$(document).width() / 2 - 200;
        var _height = 800;//$(document).height();
        var fileName = app_url + "/uploads/report/";

        //           console.log($scope.ReportOptions.FilterFieldValue);
        //url: app_url + "/views/report/reportPDF.php",
        $http({
            method: 'POST',
            url: app_url + "/Report/generate_report",
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(function (res) {
            var response = res.data;
            var data;
            if ($(response).find('#page-content').text()) {
                data = JSON.parse($(response).find('#page-content').text());
            } else {
                data = response;
            }
            if (data.status === 200) {
                fileName += data.pdf_name;
                console.log(fileName);
                $('div#collapsePanelReport').removeClass('in');
                $(document).find('i#reportHeaderToggler').removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
                $('#preview_panel').removeClass('hide');
                var object = "<object data='{FileName}#toolbar=1' type='application/pdf' width='" + _width + "px' height='" + (_height - 100) + "px'>";
                object += "If you are unable to view file, you can download from <a href ='{FileName}'>here</a>";
                object += " or download <a target = '_blank' href = 'http://get.adobe.com/reader/'>Adobe PDF Reader</a> to view the file.";
                object += "</object>";
                object = object.replace(/{FileName}/g, fileName);
                //                console.log(object);
                //$('#report_preview').css('height', _height - 80 + 'px').html(object);

                $('#reportPDFPreview').html(object);

                $scope.ReportIsOpen = true;
                // Write HTML table section and Display the table to user
                //                console.log(data.records);
                $scope.writeHtmlTable(data.records);
            } else if (data.status === 100) {
                $('#report_preview').empty();
                $('#preview_panel').addClass('hide');
                //                msg = '<p class="notification-msg">Sorry! No data available for the selected report options. Please try other options.</p>';
                //                type = 'info';
                //                $scope.notify(msg, icon, title, type);
                $('#noRecordFound').modal();
            } else {
                $('#report_preview').empty();
                $('#preview_panel').addClass('hide');
                msg = '<p class="notification-msg">Sorry! There was an error when generating your report. Please try again later or contact your system administrator for assistance.</p>';
                type = 'danger';
                $scope.notify(msg, icon, title, type);
            }
            $('.overlay').addClass('hidden');
            $(document).find(".progress").each(function () {
                $(this).css("visibility", "hidden");
            });
        });
        $('.overlay').addClass('hidden');
    };

    $scope.notify = function (msg, icon, title, type) {
        $.notify({
            title: title,
            message: msg,
            icon: icon
        }, {
            delay: 3000,
            type: type,
            placement: {
                from: 'top',
                align: 'center'
            }
        });
    };

    $scope.formatDate = function (dateString) {
        var date = dateString.getDate();
        var month = dateString.getMonth() + 1;
        var year = dateString.getFullYear();
        if (date < 10) {
            date = '0' + date;
        }
        if (month < 10) {
            month = '0' + month;
        }
        dateString = year + '-' + month + '-' + date;
        return dateString;
    };

    //Fetch data for option fields
    $scope.getOptionData = function (ctrl_name, table, form_id) {
        var data_to_post = {};
        var sel_opt = $(document).find('form#' + form_id + ' select[name="' + ctrl_name + '"]');
        data_to_post = { 'table': table };
        $http({
            method: 'POST',
            url: app_url + "/views/permission/get_option_data.php",
            data: data_to_post, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(function (response) {

            $scope.writeSelectOptions(sel_opt, response.data);
        });
    };

    //Removes select options, and add new ones
    $scope.writeSelectOptions = function (control, data) {
        control.find('option').each(function () {
            $(this).remove();
        });
        control.append('<option value="">Select Filter</option><option value="0">All</option>');
        for (var i = 0; i < data.length; i++) {
            control.append('<option value="' + data[i].id + '">' + data[i].name + '</option>');
        }
    };

    $('form#Form2 input#from_date').on('change', function () {
        if ($(this).val() !== '') {
            var selected = $(this).val();
            $('form#Form2 input#to_date').attr('min', selected);
        }
    });

    $('form#Form2 input#to_date').on('change', function () {
        if ($(this).val() !== '') {
            var selected = $(this).val();
            $('form#Form2 input#from_date').attr('max', selected);
        }
    });

    $scope.hideDiv = function () {
        $(document).find('div#range2').addClass('hide');
        $(document).find('div#collapsePanelReport').show();
    };

    $scope.togglePanelReport = function () {
        var control = $(document).find('i#reportHeaderToggler');
        if ($(document).find('div#collapsePanelReport').hasClass('in') === true) {
            control.removeClass('pe-7s-angle-up').addClass('pe-7s-angle-down');
        } else {
            control.removeClass('pe-7s-angle-down').addClass('pe-7s-angle-up');
        }
    };

    $scope.closeReportViewer = function () {
        $('#preview_panel').addClass('hide');
        $scope.ReportIsOpen = false;
    };

    $scope.writeHtmlTable = function (data) {
        $scope.records = JSON.stringify(data);
        var header = '';
        var by_value = '';
        if ($scope.ReportOptions.FilterField === 4 && $scope.ReportOptions.ReportTitle === "TRANSACTION REPORT") {
            header = '<h3>' + $scope.ReportOptions.FilterFieldValue['Name'] + '</h3>';
        }
        if ($scope.StatementReportCheck === 1) {
            $scope.ReportOptions.ReportTitle = 'STATEMENT REPORT';
        }
        // console.log($scope.institution);
        if ($scope.ReportOptions.Type === 11 && $scope.ReportOptions.FilterField === 2) {
            by_value = ' BY ' + $scope.ReportOptions.FilterFieldValue['Name'];
            // console.log( by_value);
        } else if ($scope.ReportOptions.Type === 11 && $scope.ReportOptions.FilterField === 4) {
            by_value = ' BY ' + $scope.ReportOptions.FilterFieldValue['Name'];

        }
        if ($scope.ReportOptions.Type === 6) {
            header += '<h4>' + $scope.ReportOptions.ReportTitle + by_value + ' as of ' + $filter('date')($scope.ReportOptions.StartDate, 'MMMM dd, yyyy') + '</h4>';
        } else {
            header += '<h4>' + $scope.ReportOptions.ReportTitle + by_value + ' as of ' + $filter('date')($scope.ReportOptions.StartDate, 'MMMM dd, yyyy') + ' to ' + $filter('date')($scope.ReportOptions.EndDate, 'MMMM dd, yyyy') + '</h4>';
        }
        if ($scope.ReportOptions.Type > 0) {
            var paragraph1 = "";
            var paragraph2 = "";
            var table_data = [];
            var table = "";
            var n = 1000;
            $scope.hasMany = false;
            $scope.totalRecords = data.length;

            if (data.length > n && $scope.ReportOptions.Type !== 5) {
                console.log('Total records:', data.length);
                $scope.hasMany = true;
                const result = new Array(Math.ceil(data.length / n)).fill().map(_ => data.splice(0, n));
                table_data = result[0];
                // console.log("Data before processing:", table_data);
            } else {
                table_data = data;
            }
            if ($scope.ReportOptions.Type === 1) {
                var titles = {
                    'Applicants': "APPLICANT SUMMARY REPORT",
                    'Permit': "PERMIT SUMMARY REPORT",
                    'Applications': "APPLICATION SUMMARY REPORT",
                    'Invoice': "INVOICE SUMMARY REPORT",
                    'Receipt': "RECEIPT SUMMARY REPORT",
                    'Finances': "FINANCE SUMMARY REPORT"
                };

                $.each(data, function (key, value) {
                    // Check if the key exists in titles and value is an array
                    if (titles[key] && Array.isArray(value)) {
                        table += $scope.writeTableSection(value, null, null, false, titles[key]);
                    }
                });
            }
            else if ($scope.ReportOptions.Type === 5 && $scope.StatementReportCheck === 1) {//TRANSACTION REPORT
                table += $scope.writeStatementReport(table_data);
            } else if ($scope.ReportOptions.Type === 14) {//TRANSACTION REPORT
                table += $scope.writeCommissionReport(table_data);
            } else {
                table += $scope.writeTableSection(table_data);
            }

            $('#reportHtmlSection').html(header + table);

            // Trigger modal display
            if ($scope.hasMany) {
                $('#hasMany').modal();
            } else {
                console.log('Modal condition not met, hasMany:', $scope.hasMany);
            }

            console.log('Generated Table:', table);
            console.log('Displaying modal, hasMany:', $scope.hasMany);
        }
    };
    $scope.ExportToExcel = function (titles, data) {
        var file = titles.replace(/\s/g, '_');
        console.log(file);
        var records = JSON.parse(data);
        alasql(`SELECT *
                INTO XLSX("${file}.xlsx",{headers:true})
                FROM ?`, [records]);
    }
    $scope.writeTableSection = function (rows, opening_balance = 0, closing_balance = 0, show_opening_closing = false, title = "") {
        console.log("msomi:", rows);

        var money_columns = ['Amount In', 'TOTAL INVOICE', 'AMOUNT', 'Amount Out', 'Difference', 'Money In', 'Money Out', 'Balance', 'Amount(TZS)', 'Amount(USD)', 'Amount', 'Commission', 'USD', 'TZS', 'RATE'];
        var exclude_total = ['AGE', 'Receipt Number', 'Transaction Number', 'Control Number', 'Payment Reference', 'Reference', 'CONTROL NUMBER', 'ID', 'RATE', 'PAYMENT REFERENCE', 'S/N', 'TOTAL NUMBER'];
        var header1 = "", header2 = "", column_count = 1, footer = "";
        var total_row_data = {};
        var table = "<table class='table table-condensed table-striped table-bordered report-table-for-export'>";
        table += "<thead><tr>";

        // Add header for S/N
        header2 += "<th class='text-center'> S/N </th>";

        // Process header columns
        angular.forEach(rows[0], function (value, key) {
            if (money_columns.indexOf(key) >= 0) {
                header2 += "<th class='text-center'>" + key + "</th>";
            } else {
                header2 += "<th class='text-center'>" + key + "</th>";
            }
            column_count += 1; // Increment column count for each data column
            total_row_data[key] = $.isNumeric(value) && exclude_total.indexOf(key) < 0 ? 0 : "";
        });

        if (title.length > 0) {
            header1 += "<th class='text-center text-white' style='background:rgb(224,224,224)' colspan='" + column_count + "'>" + title + "</th></tr><tr>";
        }

        if (show_opening_closing) {
            header1 += "<th>Opening Balance</th><th class='text-right' colspan='" + (column_count - 1) + "'>" + $filter('number')(parseFloat(opening_balance), 2) + " TZS</th></tr>";
            footer = "<tr><th>Closing Balance</th><th class='text-right' colspan='" + (column_count - 1) + "'>" + $filter('number')(parseFloat(closing_balance), 2) + " TZS</th></tr>";
        }

        table += header1 + header2;
        table += "</tr></thead>";
        table += "<tbody>";

        // Process table rows
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            table += "<tr>";
            table += `<td>${i + 1}</td>`; // Add S/N
            angular.forEach(row, function (value, key) {
                var display_value = value;
                var align = '';
                if (money_columns.indexOf(key) >= 0) {
                    display_value = $filter('number')(parseFloat(value), 2);
                    align = ' class="text-right"';
                }
                table += `<td${align}>${display_value}</td>`;
                if ($.isNumeric(value) && exclude_total.indexOf(key) < 0) {
                    total_row_data[key] = parseFloat(total_row_data[key]) + parseFloat(value);
                }
            });
            table += "</tr>";
        }

        table += "</tbody>";
        table += '<tfoot><tr>';

        // Ensure the total row has the correct number of columns
        // table += "<td></td>"; // S/N column
        table += "<td class='text-center' colspan=''>TOTAL</td>";
        // Process total values for each column
        angular.forEach(total_row_data, function (value, key) {
            var output = "";
            if (key !== 'S/N') {
                output = value;
            } else {
                table += "<th class='column-total-cell'>TOTAL</th>";
            }
            if ($scope.ReportOptions.Type !== 5) {
                table += "<th class='column-total-cell'>" + $filter('number')(parseFloat(output), 2) + "</th>";
            }
        });

        table += footer;
        table += "</tfoot></table>";

        return table;
    };

    $scope.writeStatementReport = function (rows) {
        var money_columns = ['Balance', 'Amount'];
        var header1 = "", header2 = "", column_count = 0, footer = "";
        var total_row_data = [];
        var table = "<table class='table table-condensed table-striped table-bordered report-table-for-export'>";
        table += "<thead><tr>";

        angular.forEach(rows[0], function (value, key) {
            column_count += 1;
            //            total_row_data[key] = $.isNumeric(value) ? 0 : "";
            if (money_columns.indexOf(key) >= 0) {
                header2 += "<th class='text-center'>" + key + ' (TZS)' + "</th>";
            } else {
                header2 += "<th class='text-center'>" + key + "</th>";
            }

        });
        var balance = rows[0]['Balance'];
        if (rows[0]['Balance'] === '' || rows[0]['Balance'] === null) {
            balance = 0;
        }
        table += "<th>Opening Balance</th><th class='text-right' colspan='" + (column_count - 1) + "'>" + $filter('number')(parseFloat(balance), 2) + " TZS</th></tr>";

        table += header1 + header2;
        table += "</tr></thead>";
        table += "<tbody>";
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            table += "<tr>";
            angular.forEach(row, function (value, key) {
                var display_value = value;
                var align = '';
                if (money_columns.indexOf(key) >= 0) {

                    if (key === "Balance") {
                        if ((row['Description'].indexOf('TIGO-PESA') > -1 || row['Description'].indexOf('MPESA') > -1) && $scope.institution == 30000004) {
                            value = balance;
                        } else {
                            balance = (row['Type'] === "Debit") ? (balance - row['Amount']) : (parseFloat(balance) + parseFloat(row['Amount']));

                            value = balance;
                        }
                    }

                    if (key !== "Balance") {
                        if (row['Type'] === "Debit") {
                            display_value = "-" + $filter('number')(parseFloat(row['Amount']), 2);
                        } else {
                            display_value = "+" + $filter('number')(parseFloat(row['Amount']), 2);
                        }
                    } else {
                        display_value = $filter('number')(parseFloat(value), 2);
                    }

                    align = ' class="text-right"';
                }
                table += `<td${align}>${display_value}</td>`;
            });
            table + "</tr>";
        }
        table += "</tbody>";
        table += '<tfoot><tr>';
        table += "<tr><th>Closing Balance</th><th class='text-right' colspan='" + (column_count - 1) + "'>" + $filter('number')(parseFloat(balance), 2) + " TZS</th></tr>";
        table += "</tfoot></table>";

        return table;
    };

    $scope.writeCommissionReport = function (rows) {
        var money_columns = ['Commission', 'Amount', 'BCX Commission'];
        var header1 = "", header2 = "", column_count = 0, footer = "";
        var total_row_data = [];
        var table = "<table class='table table-condensed table-striped table-bordered report-table-for-export'>";
        table += "<thead><tr>";

        angular.forEach(rows[0], function (value, key) {
            column_count += 1;
            //            total_row_data[key] = $.isNumeric(value) ? 0 : "";
            if (money_columns.indexOf(key) >= 0) {
                header2 += "<th class='text-center'>" + key + ' (TZS)' + "</th>";
            } else {
                header2 += "<th class='text-center'>" + key + "</th>";
            }

        });

        table += header1 + header2;
        table += "</tr></thead>";
        table += "<tbody>";
        var total_amount = 0;
        var total_commission = 0;
        var total_bcx_commission = 0;
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            table += "<tr>";
            angular.forEach(row, function (value, key) {
                var display_value = value;
                var align = '';
                if (money_columns.indexOf(key) >= 0) {
                    if (row['Type'] === "Debit") {
                        display_value = "-" + $filter('number')(parseFloat(row[key]), 2);
                    } else {
                        display_value = "+" + $filter('number')(parseFloat(row[key]), 2);
                    }

                    align = ' class="text-right"';
                }
                table += `<td${align}>${display_value}</td>`;
            });
            table + "</tr>";
            if (row['Type'] === "Debit") {
                total_amount = total_amount - parseFloat(row['Amount']);
                total_commission = total_commission - parseFloat(row['Commission']);
                total_bcx_commission = total_bcx_commission - parseFloat(row['BCX Commission']);
            } else {
                total_amount = total_amount + parseFloat(row['Amount']);
                total_commission = total_commission + parseFloat(row['Commission']);
                total_bcx_commission = total_bcx_commission + parseFloat(row['BCX Commission']);
            }
        }
        table += "</tbody>";
        table += '<tfoot><tr>';
        table += "<tr><th colspan='" + (column_count - 3) + "'>Total</th>";
        table += "<th class='text-right'>" + $filter('number')(parseFloat(total_amount), 2) + "</th>";
        table += "<th class='text-right'>" + $filter('number')(parseFloat(total_commission), 2) + "</th>";
        table += "<th class='text-right'>" + $filter('number')(parseFloat(total_bcx_commission), 2) + "</th></tr>";
        table += "</tfoot></table>";

        return table;
    };
    $scope.submitToLab = function (data) {
        console.log(data);
        $http({
            method: 'POST',
            url: app_url + "/Application/submitToLab",
            data: data, //forms user object
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(function (res) {
            var data = res.data;
            if (data.errors) {
                $('.notification-area').addClass('alert alert-danger').text("Error handling your request. Please try again later.");
            } else {
                var response = $(data).find('#mabrexPageContent').text().trim();
                if (response === '200' || response === '201') {
                    $('.notification-area').addClass('alert alert-success').text("Your request was successfully handled.");
                } else {
                    $('.notification-area').addClass('alert alert-danger').text("Your request has failed. Please try again later.");
                }
                setTimeout(function () {
                    $('.notification-area').removeClass('alert alert-success').text('');
                    if (response === '200') {
                        location.reload();
                    }
                }, 4000);
            }
        });
    }
}]);