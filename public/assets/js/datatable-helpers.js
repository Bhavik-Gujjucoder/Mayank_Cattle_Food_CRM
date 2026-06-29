/**
 * DataTables helpers — safe loading, debounced search, and resilient AJAX on slow networks.
 */

window.whenDataTablesReady = function (callback, maxAttempts) {
    var attempts = 0;
    maxAttempts = maxAttempts || 100;

    function check() {
        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
            callback(jQuery);
            return;
        }

        if (++attempts >= maxAttempts) {
            console.error('DataTables failed to load');
            if (typeof show_error === 'function') {
                show_error('Table library failed to load. Please refresh the page.');
            }
            return;
        }

        setTimeout(check, 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', check);
    } else {
        check();
    }
};

window.withDataTable = function (initFn) {
    whenDataTablesReady(function ($) {
        $(function () {
            initFn($);
        });
    });
};

window.handleDataTableAjaxError = function (xhr, textStatus, tableApi) {
    if (textStatus === 'timeout') {
        if (typeof show_error === 'function') {
            show_error('Request timed out. Retrying...');
        }
        if (tableApi && tableApi.ajax) {
            setTimeout(function () {
                tableApi.ajax.reload(null, false);
            }, 2000);
        }
        return;
    }

    if (xhr && (xhr.status === 401 || xhr.status === 419)) {
        if (typeof show_error === 'function') {
            show_error('Session expired. Please login again.');
        }
        setTimeout(function () {
            window.location.href = window.APP_LOGIN_URL || '/login';
        }, 2000);
        return;
    }

    if (typeof show_error === 'function') {
        show_error('Failed to load table data. Please try again.');
    }
};

window.buildDataTableAjax = function (url, options) {
    options = options || {};
    var tableRef = { api: null };

    var config = {
        url: url,
        type: options.type || 'GET',
        timeout: options.timeout || 60000,
        error: function (xhr, textStatus) {
            handleDataTableAjaxError(xhr, textStatus, tableRef.api);
        },
    };

    if (typeof options.data === 'function') {
        config.data = options.data;
    }

    config._bindTable = function (api) {
        tableRef.api = api;
    };

    return config;
};

/**
 * Debounced DataTables search — reduces AJAX calls while typing on slow networks.
 */
window.bindDebouncedDataTableSearch = function (selector, dataTableApi, delayMs) {
    delayMs = delayMs || 400;
    var timer;
    $(selector)
        .off('keyup.debouncedDtSearch')
        .on('keyup.debouncedDtSearch', function () {
            clearTimeout(timer);
            var value = this.value;
            timer = setTimeout(function () {
                dataTableApi.search(value).draw();
            }, delayMs);
        });
};
