(function () {
    'use strict';

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
                        msg += ' — ' + parts.join(', ');
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
