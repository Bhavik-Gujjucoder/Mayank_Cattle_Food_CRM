@extends('layouts.main')

@section('title')
    {{ $page_title }}
@endsection

@section('content')
    <div id="backup-page-alert" class="d-none"></div>

    <div id="backup-init-banner" class="alert alert-warning {{ $initialized ? 'd-none' : '' }}">
        <strong>Backup system is not initialized.</strong>
        Generate server encryption keys before creating or restoring backups.
        <button type="button" id="backup-init-btn" class="btn btn-sm btn-warning ms-2">
            Initialize Backup System
        </button>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card" id="create-backup-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-database-export me-1"></i> Create Backup
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Creates an encrypted backup of the database and <code>storage/app/public</code>.
                        Store your passphrase safely. Backups cannot be restored without it.
                    </p>

                    <form id="create-backup-form" action="{{ route('system.backup.create') }}" method="POST" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="col-form-label">Backup Passphrase <span class="text-danger">*</span></label>
                            <input type="password" name="create_passphrase" id="create_passphrase"
                                class="form-control" minlength="8" autocomplete="new-password"
                                {{ $initialized ? '' : 'disabled' }}>
                            <div class="invalid-feedback d-block create_passphrase_error"></div>
                        </div>

                        <div class="mb-3">
                            <label class="col-form-label">Confirm Passphrase <span class="text-danger">*</span></label>
                            <input type="password" name="create_passphrase_confirmation"
                                id="create_passphrase_confirmation" class="form-control" minlength="8"
                                autocomplete="new-password" {{ $initialized ? '' : 'disabled' }}>
                            <div class="invalid-feedback d-block create_passphrase_confirmation_error"></div>
                        </div>

                        <button type="submit" id="create-backup-btn" class="btn btn-primary"
                            {{ $initialized ? '' : 'disabled' }}>
                            <span class="btn-label">Create Encrypted Backup</span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span> Creating...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card" id="restore-backup-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-database-import me-1"></i> Restore Backup
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger py-2 mb-3">
                        <strong>Warning:</strong> Restore will overwrite the current database and
                        files. This action cannot be undone.
                    </div>

                    <form id="restore-backup-form" action="{{ route('system.backup.restore') }}" method="POST"
                        enctype="multipart/form-data" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label class="col-form-label d-block mb-2">Restore From</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input restore-source" type="radio" name="restore_source"
                                    id="restore_source_server" value="server" checked
                                    {{ $initialized ? '' : 'disabled' }}>
                                <label class="form-check-label" for="restore_source_server">Server backup</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input restore-source" type="radio" name="restore_source"
                                    id="restore_source_upload" value="upload"
                                    {{ $initialized ? '' : 'disabled' }}>
                                <label class="form-check-label" for="restore_source_upload">Upload file</label>
                            </div>
                            <div class="text-danger small mt-1 restore_source_error"></div>
                        </div>

                        <div class="mb-3 restore-server-field">
                            <label class="col-form-label">Server Backup <span class="text-danger">*</span></label>
                            <select name="backup_filename" id="backup_filename" class="form-select"
                                {{ $initialized ? '' : 'disabled' }}>
                                <option value="">Select backup file</option>
                            </select>
                            <div class="invalid-feedback d-block backup_filename_error"></div>
                        </div>

                        <div class="mb-3 restore-upload-field d-none">
                            <label class="col-form-label">Upload Backup File <span class="text-danger">*</span></label>
                            <input type="file" name="backup_file" id="backup_file" class="form-control"
                                accept=".{{ config('backup.extension') }}" disabled
                                {{ $initialized ? '' : 'disabled' }}>
                            <small class="text-muted">Only .{{ config('backup.extension') }} encrypted backup files are accepted.</small>
                            <div class="invalid-feedback d-block backup_file_error"></div>
                        </div>

                        <div class="mb-3">
                            <label class="col-form-label">Your Account Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="restore_password" class="form-control"
                                autocomplete="current-password" {{ $initialized ? '' : 'disabled' }}>
                            <div class="invalid-feedback d-block password_error"></div>
                        </div>

                        <div class="mb-3 restore-passphrase-field d-none">
                            <label class="col-form-label">Backup Passphrase <span class="text-danger">*</span></label>
                            <input type="password" name="restore_passphrase" id="restore_passphrase"
                                class="form-control" minlength="8" autocomplete="new-password"
                                disabled {{ $initialized ? '' : 'disabled' }}>
                            <small class="text-muted">Required when uploading a backup file from another location.</small>
                            <div class="invalid-feedback d-block restore_passphrase_error"></div>
                        </div>

                        <div class="mb-3 restore-passphrase-note restore-server-field">
                            <small class="text-muted">
                                The backup passphrase saved when this backup was created will be used automatically.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="col-form-label">Type <strong>RESTORE</strong> to confirm <span class="text-danger">*</span></label>
                            <input type="text" name="confirmation_text" id="confirmation_text" class="form-control"
                                placeholder="RESTORE" {{ $initialized ? '' : 'disabled' }}>
                            <div class="invalid-feedback d-block confirmation_text_error"></div>
                        </div>

                        <button type="submit" id="restore-backup-btn" class="btn btn-danger"
                            {{ $initialized ? '' : 'disabled' }}>
                            <span class="btn-label">Restore Backup</span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span> Restoring...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-sm-4">
                    <div class="icon-form mb-3 mb-sm-0">
                        <span class="form-icon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="backupCustomSearch" placeholder="Search backups"
                            {{ $initialized ? '' : 'disabled' }}>
                    </div>
                </div>
                <div class="col-sm-8">
                    <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">
                        <button type="button" id="refresh-backup-list-btn" class="btn btn-outline-secondary"
                            {{ $initialized ? '' : 'disabled' }}>
                            <i class="ti ti-refresh"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive custom-table">
                <table class="table dataTable no-footer" id="backup_history_table">
                    <thead class="thead-light">
                        <tr>
                            <th hidden>ID</th>
                            <th class="no-sort" scope="col">Sr no</th>
                            <th scope="col">Filename</th>
                            <th scope="col">Size</th>
                            <th scope="col">Created At</th>
                            <th scope="col">Created By</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('datatable-scripts')
    @include('partials.datatable-scripts')
@endpush

@section('script')
<script>
    (function() {
        var backupPageConfig = {
            initialized: @json($initialized),
            routes: {
                list: @json(route('system.backup.list')),
                initialize: @json(route('system.backup.initialize')),
                create: @json(route('system.backup.create')),
                restore: @json(route('system.backup.restore')),
                destroy: @json(route('system.backup.destroy', ['backup' => '__id__'])),
            },
            csrfToken: @json(csrf_token()),
        };

        var config = backupPageConfig;
        var initialized = !!config.initialized;
        var backup_table = null;

        function getCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                return meta.getAttribute('content');
            }
            return config.csrfToken;
        }

        function ajaxHeaders() {
            return {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
        }

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function showPageAlert(message, type) {
            var alertType = type === 'success' ? 'alert-success' : 'alert-danger';
            var label = type === 'success' ? '' : '<strong>Error:</strong> ';

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
                var message = Array.isArray(messages) ? messages[0] : messages;
                var $input = $form.find('[name="' + field + '"]');

                if ($input.length) {
                    $input.addClass('is-invalid');
                }

                var $error = $form.find('.' + field + '_error');
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
            var isUpload = $('.restore-source:checked').val() === 'upload';

            $('.restore-server-field').toggleClass('d-none', isUpload);
            $('.restore-upload-field').toggleClass('d-none', !isUpload);
            $('.restore-passphrase-field').toggleClass('d-none', !isUpload);
            $('.restore-passphrase-note').toggleClass('d-none', isUpload);
            $('#backup_filename').prop('disabled', isUpload || !initialized);
            $('#backup_file').prop('disabled', !isUpload || !initialized);
            $('#restore_passphrase').prop('disabled', !isUpload || !initialized);
        }

        function renderBackupOptions(backups) {
            var $select = $('#backup_filename');
            var currentValue = $select.val();

            $select.empty().append('<option value="">Select backup file</option>');

            (backups || []).forEach(function(backup) {
                var label = backup.filename + ' (' + backup.size_label + ', ' + backup.modified_at + ')';
                $select.append($('<option>', {
                    value: backup.filename,
                    text: label
                }));
            });

            if (currentValue) {
                $select.val(currentValue);
            }
        }

        function initBackupTable() {
            if (backup_table) {
                backup_table.ajax.reload(null, true);
                return;
            }

            var backupAjax = buildDataTableAjax(config.routes.list);

            backup_table = $('#backup_history_table').DataTable({
                pageLength: 10,
                deferRender: true,
                processing: true,
                serverSide: true,
                responsive: true,
                dom: 'lrtip',
                order: [[0, 'desc']],
                ajax: backupAjax,
                columns: [
                    {
                        data: 'id',
                        name: 'id',
                        visible: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'filename',
                        name: 'filename',
                        searchable: true
                    },
                    {
                        data: 'size_label',
                        name: 'size_label',
                        searchable: false
                    },
                    {
                        data: 'modified_at',
                        name: 'modified_at',
                        searchable: false
                    },
                    {
                        data: 'created_by_name',
                        name: 'created_by_name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            backupAjax._bindTable(backup_table);
            bindDebouncedDataTableSearch('#backupCustomSearch', backup_table);
        }

        function refreshBackupOptions() {
            return $.ajax({
                url: config.routes.list + '?for=options',
                type: 'GET',
                headers: ajaxHeaders(),
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    renderBackupOptions(response.backups || []);
                }
            });
        }

        function reloadBackupData(showToast, resetPaging) {
            if (backup_table) {
                backup_table.ajax.reload(null, resetPaging !== false);
            }

            return refreshBackupOptions().done(function() {
                if (showToast) {
                    show_success('Backup list refreshed.');
                }
            });
        }

        function enableBackupUi() {
            initialized = true;
            $('#backup-init-banner').addClass('d-none');
            $('#create-backup-form :input, #restore-backup-form :input, #create-backup-btn, #restore-backup-btn, #refresh-backup-list-btn, #backupCustomSearch')
                .prop('disabled', false);
            toggleRestoreFields();
            initBackupTable();
        }

        function refreshBackupList(showToast) {
            return reloadBackupData(showToast).fail(function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Unable to refresh backup list.';
                show_error(message);
            });
        }

        function handleAjaxError($form, xhr) {
            if (xhr.status === 422) {
                var hasFieldErrors = xhr.responseJSON && xhr.responseJSON.errors
                    && Object.keys(xhr.responseJSON.errors).length > 0;

                if (hasFieldErrors) {
                    showFormErrors($form, xhr.responseJSON.errors);
                    clearPageAlert();
                    return;
                }

                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Please correct the highlighted fields.';
                show_error(message);
                return;
            }

            if (xhr.status === 419) {
                show_error('Session expired. Please refresh the page and try again.');
                return;
            }

            var message = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Something went wrong. Please try again.';
            show_error(message);
        }

        function submitCreateBackup($form) {
            var $btn = $('#create-backup-btn');

            clearPageAlert();
            clearFormErrors($form);
            setButtonLoading($btn, true);

            $.ajax({
                url: config.routes.create,
                type: 'POST',
                headers: ajaxHeaders(),
                data: $form.serialize(),
                dataType: 'json',
                timeout: 600000
            }).done(function(response) {
                $form.trigger('reset');
                $form.validate().resetForm();
                reloadBackupData(false, true);
                show_success(response.message);
            }).fail(function(xhr) {
                handleAjaxError($form, xhr);
            }).always(function() {
                setButtonLoading($btn, false);
            });
        }

        function submitRestoreBackup($form) {
            var $btn = $('#restore-backup-btn');

            clearPageAlert();
            clearFormErrors($form);
            setButtonLoading($btn, true);

            var formData = new FormData($form[0]);
            formData.set('_token', getCsrfToken());

            $.ajax({
                url: config.routes.restore,
                type: 'POST',
                headers: ajaxHeaders(),
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 600000
            }).done(function(response) {
                $form.trigger('reset');
                $form.validate().resetForm();
                toggleRestoreFields();
                show_success(response.message);
            }).fail(function(xhr) {
                handleAjaxError($form, xhr);
            }).always(function() {
                setButtonLoading($btn, false);
            });
        }

        function initBackupValidators() {
            $.validator.addMethod('restoreConfirm', function(value) {
                return value === 'RESTORE';
            }, 'Type RESTORE exactly to confirm this action.');

            $('#create-backup-form').validate({
                rules: {
                    create_passphrase: {
                        required: true,
                        minlength: 8
                    },
                    create_passphrase_confirmation: {
                        required: true,
                        equalTo: '#create_passphrase'
                    }
                },
                messages: {
                    create_passphrase: {
                        required: 'Backup passphrase is required.',
                        minlength: 'Backup passphrase must be at least 8 characters.'
                    },
                    create_passphrase_confirmation: {
                        required: 'Please confirm the backup passphrase.',
                        equalTo: 'Backup passphrase confirmation does not match.'
                    }
                },
                errorElement: 'span',
                errorClass: 'text-danger',
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                    var name = $(element).attr('name');
                    $('.' + name + '_error').text('');
                },
                errorPlacement: function(error, element) {
                    var name = element.attr('name');
                    var $holder = $('.' + name + '_error');
                    if ($holder.length) {
                        $holder.text(error.text());
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    submitCreateBackup($(form));
                }
            });

            $('#restore-backup-form').validate({
                rules: {
                    backup_filename: {
                        required: function() {
                            return $('input[name="restore_source"]:checked').val() === 'server';
                        }
                    },
                    backup_file: {
                        required: function() {
                            return $('input[name="restore_source"]:checked').val() === 'upload';
                        }
                    },
                    password: {
                        required: true
                    },
                    restore_passphrase: {
                        required: function() {
                            return $('input[name="restore_source"]:checked').val() === 'upload';
                        },
                        minlength: 8
                    },
                    confirmation_text: {
                        required: true,
                        restoreConfirm: true
                    }
                },
                messages: {
                    backup_filename: {
                        required: 'Please select a backup from the server.'
                    },
                    backup_file: {
                        required: 'Please upload a backup file.'
                    },
                    password: {
                        required: 'Your account password is required.'
                    },
                    restore_passphrase: {
                        required: 'Backup passphrase is required.',
                        minlength: 'Backup passphrase must be at least 8 characters.'
                    },
                    confirmation_text: {
                        required: 'Type RESTORE to confirm this action.'
                    }
                },
                errorElement: 'span',
                errorClass: 'text-danger',
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                    var name = $(element).attr('name');
                    $('.' + name + '_error').text('');
                },
                errorPlacement: function(error, element) {
                    var name = element.attr('name');
                    var $holder = $('.' + name + '_error');
                    if ($holder.length) {
                        $holder.text(error.text());
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    submitRestoreBackup($(form));
                }
            });
        }

        withDataTable(function() {
            if (initialized) {
                initBackupTable();
                refreshBackupOptions();
            }
        });

        $(function() {
            if (typeof $ === 'undefined') {
                console.error('jQuery is not loaded. System backup page cannot initialize.');
                return;
            }

            toggleRestoreFields();
            initBackupValidators();

            $('.restore-source').on('change', function() {
                toggleRestoreFields();
                $('#restore-backup-form').validate().resetForm();
                clearFormErrors($('#restore-backup-form'));
            });

            $('#backup-init-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Initializing...');
                clearPageAlert();

                $.ajax({
                    url: config.routes.initialize,
                    type: 'POST',
                    headers: ajaxHeaders(),
                    data: {
                        _token: getCsrfToken()
                    },
                    dataType: 'json'
                }).done(function(response) {
                    enableBackupUi();
                    renderBackupOptions(response.backups || []);
                    show_success(response.message);
                }).fail(function(xhr) {
                    handleAjaxError($('#create-backup-form'), xhr);
                }).always(function() {
                    $btn.prop('disabled', false).text('Initialize Backup System');
                });
            });

            $('#refresh-backup-list-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true);
                refreshBackupList(true).always(function() {
                    $btn.prop('disabled', false);
                });
            });

            $(document).on('click', '.delete-backup-btn', function() {
                var $btn = $(this);
                var backupId = $btn.data('id');
                var filename = $btn.data('filename');

                confirmBackupDeletion(filename, function() {
                    $btn.prop('disabled', true);

                    $.ajax({
                        url: config.routes.destroy.replace('__id__', backupId),
                        type: 'DELETE',
                        headers: ajaxHeaders(),
                        dataType: 'json'
                    }).done(function(response) {
                        reloadBackupData(false, false);
                        show_success(response.message);
                    }).fail(function(xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'Unable to delete backup.';
                        show_error(message);
                    }).always(function() {
                        $btn.prop('disabled', false);
                    });
                });
            });
        });

        function confirmBackupDeletion(filename, callback) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete backup "' + escapeHtml(filename) + '"? This will remove the backup file and its database record permanently.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'my-custom-popup',
                    title: 'my-custom-title',
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary',
                    icon: 'my-custom-icon swal2-warning'
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    callback();
                }
            });
        }
    })();
</script>
@endsection
