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
