(function($) {
    'use strict';

    const config = window.backupPageConfig || {};
    let initialized = !!config.initialized;

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function showPageAlert(message, type) {
        const alertType = type === 'success' ? 'alert-success' : 'alert-danger';
        const label = type === 'success' ? '' : '<strong>Error:</strong> ';

        $('#backup-page-alert')
            .removeClass('d-none alert-success alert-danger')
            .addClass(alertType)
            .html(
                label + escapeHtml(message) +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            );
    }

    function clearPageAlert() {
        $('#backup-page-alert').addClass('d-none').empty();
    }

    function clearFormErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[class$="_error"]').text('');
    }

    function showFormErrors($form, errors) {
        clearFormErrors($form);

        if (!errors) {
            return;
        }

        $.each(errors, function(field, messages) {
            const message = Array.isArray(messages) ? messages[0] : messages;
            const $input = $form.find('[name="' + field + '"]');

            if ($input.length) {
                $input.addClass('is-invalid');
            }

            const $error = $form.find('.' + field + '_error');
            if ($error.length) {
                $error.text(message).addClass('text-danger');
            }
        });
    }

    function setButtonLoading($button, loading) {
        $button.prop('disabled', loading);
        $button.find('.btn-label').toggleClass('d-none', loading);
        $button.find('.btn-loading').toggleClass('d-none', !loading);
    }

    function toggleRestoreFields() {
        const isUpload = $('.restore-source:checked').val() === 'upload';

        $('.restore-server-field').toggleClass('d-none', isUpload);
        $('.restore-upload-field').toggleClass('d-none', !isUpload);
        $('#backup_filename').prop('disabled', isUpload || !initialized);
        $('#backup_file').prop('disabled', !isUpload || !initialized);
    }

    function renderBackupOptions(backups) {
        const $select = $('#backup_filename');
        const currentValue = $select.val();

        $select.empty().append('<option value="">Select backup file</option>');

        (backups || []).forEach(function(backup) {
            const label = backup.filename + ' (' + backup.size_label + ', ' + backup.modified_at + ')';
            $select.append(
                $('<option>', {
                    value: backup.filename,
                    text: label,
                })
            );
        });

        if (currentValue) {
            $select.val(currentValue);
        }
    }

    function renderBackupHistory(backups) {
        const items = backups || [];
        const $tbody = $('#backup-history-tbody');
        const $empty = $('.backup-history-empty');
        const $table = $('.backup-history-table');

        $tbody.empty();

        if (!items.length) {
            $empty.removeClass('d-none');
            $table.addClass('d-none');
            return;
        }

        $empty.addClass('d-none');
        $table.removeClass('d-none');

        items.forEach(function(backup) {
            $tbody.append(
                '<tr>' +
                    '<td>' + escapeHtml(backup.filename) + '</td>' +
                    '<td>' + escapeHtml(backup.size_label) + '</td>' +
                    '<td>' + escapeHtml(backup.modified_at) + '</td>' +
                    '<td class="text-end">' +
                        '<a href="' + escapeHtml(backup.download_url) + '" class="btn btn-sm btn-outline-primary">' +
                            '<i class="ti ti-download"></i> Download' +
                        '</a>' +
                    '</td>' +
                '</tr>'
            );
        });
    }

    function enableBackupUi() {
        initialized = true;
        $('#backup-init-banner').addClass('d-none');
        $('#create-backup-form :input, #restore-backup-form :input, #create-backup-btn, #restore-backup-btn, #refresh-backup-list-btn')
            .prop('disabled', false);
        toggleRestoreFields();
    }

    function refreshBackupList(showToast) {
        return $.ajax({
            url: config.routes.list,
            type: 'GET',
            dataType: 'json',
        }).done(function(response) {
            if (!response.success) {
                return;
            }

            initialized = !!response.initialized;
            renderBackupHistory(response.backups);
            renderBackupOptions(response.backups);

            if (showToast) {
                show_success('Backup list refreshed.');
            }
        }).fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'Unable to refresh backup list.';
            show_error(message);
        });
    }

    function handleAjaxError($form, xhr) {
        if (xhr.status === 422) {
            if (xhr.responseJSON?.errors) {
                showFormErrors($form, xhr.responseJSON.errors);
            }

            const message = xhr.responseJSON?.message || 'Please correct the highlighted fields.';
            showPageAlert(message, 'error');
            show_error(message);
            return;
        }

        const message = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
        showPageAlert(message, 'error');
        show_error(message);
    }

    $(function() {
        renderBackupHistory(config.backups);
        renderBackupOptions(config.backups);
        toggleRestoreFields();

        $('.restore-source').on('change', toggleRestoreFields);

        $('#backup-init-btn').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('Initializing...');
            clearPageAlert();

            $.ajax({
                url: config.routes.initialize,
                type: 'POST',
                data: {
                    _token: $('input[name="_token"]').first().val(),
                },
                dataType: 'json',
            }).done(function(response) {
                enableBackupUi();
                renderBackupHistory(response.backups || []);
                renderBackupOptions(response.backups || []);
                showPageAlert(response.message, 'success');
                show_success(response.message);
            }).fail(function(xhr) {
                handleAjaxError($('#create-backup-form'), xhr);
            }).always(function() {
                $btn.prop('disabled', false).text('Initialize Backup System');
            });
        });

        $('#refresh-backup-list-btn').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true);
            refreshBackupList(true).always(function() {
                $btn.prop('disabled', false);
            });
        });

        $('#create-backup-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $('#create-backup-btn');

            clearPageAlert();
            clearFormErrors($form);
            setButtonLoading($btn, true);

            $.ajax({
                url: config.routes.create,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                timeout: 0,
            }).done(function(response) {
                $form.trigger('reset');
                renderBackupHistory(response.backups || []);
                renderBackupOptions(response.backups || []);
                showPageAlert(response.message, 'success');
                show_success(response.message);
            }).fail(function(xhr) {
                handleAjaxError($form, xhr);
            }).always(function() {
                setButtonLoading($btn, false);
            });
        });

        $('#restore-backup-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $('#restore-backup-btn');

            clearPageAlert();
            clearFormErrors($form);
            setButtonLoading($btn, true);

            const formData = new FormData(this);

            $.ajax({
                url: config.routes.restore,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 0,
            }).done(function(response) {
                show_success(response.message);

                if (response.reload) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                    return;
                }

                showPageAlert(response.message, 'success');
                $form.trigger('reset');
                toggleRestoreFields();
            }).fail(function(xhr) {
                handleAjaxError($form, xhr);
            }).always(function() {
                setButtonLoading($btn, false);
            });
        });
    });
})(jQuery);
