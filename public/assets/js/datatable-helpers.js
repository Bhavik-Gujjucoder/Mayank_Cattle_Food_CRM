/**
 * DataTables helpers — safe loading, debounced search, and resilient AJAX on slow networks.
 */

window.whenDataTablesReady = function (callback, maxAttempts) {
    var attempts = 0;
    maxAttempts = maxAttempts || 100;

    function check() {
        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    info: 'Showing _START_ to _END_ of <span class="dt-info-total">_TOTAL_</span> entries',
                    infoEmpty: 'Showing 0 to 0 of <span class="dt-info-total">0</span> entries'
                }
            });
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
/**
 * Restore the list page, search, and filters before a table is created.
 * Does not use DataTables stateSave, which was hiding every row.
 */
window.dataTablePageMemory = function (tableSelector, filters, searchSelector) {
    filters = filters || [];
    var tableId = String(tableSelector || '').replace('#', '');
    var legacyPrefix = 'DataTables_' + tableId + '_';

    try {
        for (var i = localStorage.length - 1; i >= 0; i--) {
            var storageKey = localStorage.key(i);
            if (storageKey && storageKey.indexOf(legacyPrefix) === 0) {
                localStorage.removeItem(storageKey);
            }
        }
    } catch (e) {}

    var memoryKey = 'list-page:' + window.location.pathname + ':' + tableId;
    var saved = {};
    try {
        saved = JSON.parse(sessionStorage.getItem(memoryKey) || '{}') || {};
    } catch (e) {
        saved = {};
    }

    var filterValues = saved.filters || {};
    filters.forEach(function (filter) {
        if (filterValues[filter.key] === undefined || filterValues[filter.key] === '') {
            return;
        }
        var $field = $(filter.selector);
        if (!$field.length) {
            return;
        }
        $field.val(filterValues[filter.key]);
        if (filter.select2 && $field.hasClass('select2-hidden-accessible')) {
            $field.trigger('change.select2');
        }
    });

    if (searchSelector && saved.search) {
        $(searchSelector).val(saved.search);
    }

    $(tableSelector).data('listPageMemory', {
        key: memoryKey,
        filters: filters,
        searchSelector: searchSelector || ''
    });

    var page = parseInt(saved.page, 10);
    var length = parseInt(saved.length, 10);
    if (isNaN(page) || page < 0) {
        page = 0;
    }
    if (isNaN(length) || length < 1) {
        length = 10;
    }

    var options = {};
    if (page > 0) {
        options.displayStart = page * length;
    }
    if (saved.search) {
        options.search = { search: saved.search };
    }

    return options;
};

/** Remember the current page, and step back when a delete leaves the page empty. */
window.keepDataTablePage = function (table) {
    var $node = $(table.table().node());
    var memory = $node.data('listPageMemory') || {};
    var memoryKey = memory.key;
    var filters = memory.filters || [];
    var searchSelector = memory.searchSelector || '';
    var adjusting = false;

    function persist() {
        if (!memoryKey) {
            return;
        }
        var info = table.page.info();
        var filterValues = {};
        filters.forEach(function (filter) {
            filterValues[filter.key] = $(filter.selector).val() || '';
        });
        try {
            sessionStorage.setItem(memoryKey, JSON.stringify({
                page: info.page,
                length: info.length,
                search: searchSelector ? ($(searchSelector).val() || '') : (table.search() || ''),
                filters: filterValues
            }));
        } catch (e) {}
    }

    table.on('draw.dt', function () {
        persist();
        if (adjusting) {
            adjusting = false;
            return;
        }
        var info = table.page.info();
        var pageLength = info.length || 10;
        if (info.page > 0 && info.recordsDisplay <= info.page * pageLength) {
            var lastPage = Math.max(0, Math.ceil(info.recordsDisplay / pageLength) - 1);
            if (lastPage !== info.page) {
                adjusting = true;
                table.page(lastPage).draw('page');
            }
        }
    });
};

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
