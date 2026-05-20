(function () {
    'use strict';

    angular.module('zantechApp')
        .service('FormLoader', ['$http', '$q', '$compile', '$timeout', function ($http, $q, $compile, $timeout) {

            this.load = function (url) {
                var deferred = $q.defer();
                $('.overlay').removeClass('hidden'); // Show spinner

                $.ajax({
                    url: url,
                    type: 'GET',
                    error: function (xhr) {
                        $('.overlay').addClass('hidden');
                        deferred.reject("Error loading form: " + xhr.status + " " + xhr.statusText);
                    },
                    success: function (responseText) {
                        try {
                            // Wrap response so #page-content is always a descendant
                            var $wrap = $('<div>').append($.parseHTML(responseText, document, true));
                            var $pageContent = $wrap.find('#page-content');

                            if (!$pageContent.length) {
                                deferred.reject("Error loading form: #page-content not found in response");
                                return;
                            }

                            var result = {
                                template: $pageContent.find('#display_content').html(),
                                data: {},
                                extras: {}
                            };

                            var dataContent = $pageContent.find('#data_content');

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

                            deferred.resolve(result);
                        } catch (e) {
                            $('.overlay').addClass('hidden');
                            deferred.reject("Error parsing form data: " + e.message);
                        }
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
