(function($){
    'use strict';

    function hasAjaxConfig() {
        return typeof cpc_projects_ajax !== 'undefined' && !!cpc_projects_ajax.ajaxurl;
    }

    function replaceTaskPanel(projectId, tasksHtml) {
        var $panel = $('.cpc_projects_task_panel[data-project-id="' + projectId + '"]');
        if ($panel.length && tasksHtml) {
            $panel.replaceWith(tasksHtml);
            applyTaskFilters($('.cpc_projects_task_panel[data-project-id="' + projectId + '"]'));
        }
    }

    function applyTaskFilters($panel) {
        if (!$panel || !$panel.length) {
            return;
        }

        var textValue = $.trim(($panel.find('.cpc_projects_task_filter_text').val() || '')).toLowerCase();
        var statusValue = ($panel.find('.cpc_projects_task_filter_status').val() || 'all');
        var visibleCount = 0;

        $panel.find('.cpc_projects_task_item').each(function() {
            var $item = $(this);
            var taskTitle = ($item.attr('data-task-title') || '').toLowerCase();
            var taskStatus = $item.attr('data-task-status') || 'open';

            var textMatch = !textValue || taskTitle.indexOf(textValue) !== -1;
            var statusMatch = (statusValue === 'all') || (taskStatus === statusValue);
            var show = textMatch && statusMatch;

            $item.toggle(show);
            if (show) {
                visibleCount += 1;
            }
        });

        $panel.find('.cpc_projects_no_tasks_filtered').toggle(visibleCount === 0 && $panel.find('.cpc_projects_task_item').length > 0);
    }

    $(document).on('submit', '.cpc_projects_task_form', function(e) {
        e.preventDefault();
        if (!hasAjaxConfig()) {
            return;
        }

        var $form = $(this);
        var projectId = parseInt($form.data('project-id'), 10);
        if (!projectId) {
            return;
        }

        var payload = {
            action: 'cpc_projects_add_task',
            nonce: cpc_projects_ajax.nonce,
            project_id: projectId,
            title: $.trim($form.find('[name="title"]').val() || ''),
            description: $.trim($form.find('[name="description"]').val() || ''),
            priority: parseInt($form.find('[name="priority"]').val(), 10) || 1,
            deadline: $.trim($form.find('[name="deadline"]').val() || '')
        };

        payload.assigned_user_ids = $form.find('[name="assigned_user_ids[]"]').val() || [];

        if (!payload.title) {
            return;
        }

        $.post(cpc_projects_ajax.ajaxurl, payload).done(function(resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.tasks_html) {
                alert(cpc_projects_ajax.addTaskError || 'Task konnte nicht erstellt werden.');
                return;
            }

            replaceTaskPanel(projectId, resp.data.tasks_html);
        }).fail(function() {
            alert(cpc_projects_ajax.addTaskError || 'Task konnte nicht erstellt werden.');
        });
    });

    $(document).on('change', '.cpc_projects_task_toggle', function() {
        if (!hasAjaxConfig()) {
            return;
        }

        var $checkbox = $(this);
        var taskId = parseInt($checkbox.data('task-id'), 10);
        if (!taskId) {
            return;
        }

        var $panel = $checkbox.closest('.cpc_projects_task_panel');
        var projectId = parseInt($panel.data('project-id'), 10);
        if (!projectId) {
            return;
        }

        $.post(cpc_projects_ajax.ajaxurl, {
            action: 'cpc_projects_toggle_task',
            nonce: cpc_projects_ajax.nonce,
            task_id: taskId,
            status: $checkbox.is(':checked') ? 'done' : 'open'
        }).done(function(resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.tasks_html) {
                return;
            }

            replaceTaskPanel(projectId, resp.data.tasks_html);
        });
    });

    $(document).on('click', '.cpc_projects_task_delete', function(e) {
        e.preventDefault();
        if (!hasAjaxConfig()) {
            return;
        }

        if (!window.confirm(cpc_projects_ajax.confirmDeleteTask || 'Task wirklich loeschen?')) {
            return;
        }

        var $btn = $(this);
        var taskId = parseInt($btn.data('task-id'), 10);
        if (!taskId) {
            return;
        }

        var $panel = $btn.closest('.cpc_projects_task_panel');
        var projectId = parseInt($panel.data('project-id'), 10);
        if (!projectId) {
            return;
        }

        $.post(cpc_projects_ajax.ajaxurl, {
            action: 'cpc_projects_delete_task',
            nonce: cpc_projects_ajax.nonce,
            task_id: taskId
        }).done(function(resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.tasks_html) {
                return;
            }

            replaceTaskPanel(projectId, resp.data.tasks_html);
        });
    });

    $(document).on('click', '.cpc_projects_task_edit_toggle', function(e) {
        e.preventDefault();
        var taskId = parseInt($(this).data('task-id'), 10);
        if (!taskId) {
            return;
        }

        var $item = $(this).closest('.cpc_projects_task_item');
        $item.find('.cpc_projects_task_edit_form').first().toggle();
    });

    $(document).on('submit', '.cpc_projects_task_edit_form', function(e) {
        e.preventDefault();
        if (!hasAjaxConfig()) {
            return;
        }

        var $form = $(this);
        var taskId = parseInt($form.data('task-id'), 10);
        if (!taskId) {
            return;
        }

        var $panel = $form.closest('.cpc_projects_task_panel');
        var projectId = parseInt($panel.data('project-id'), 10);
        if (!projectId) {
            return;
        }

        var payload = {
            action: 'cpc_projects_update_task',
            nonce: cpc_projects_ajax.nonce,
            task_id: taskId,
            title: $.trim($form.find('[name="title"]').val() || ''),
            description: $.trim($form.find('[name="description"]').val() || ''),
            priority: parseInt($form.find('[name="priority"]').val(), 10) || 1,
            deadline: $.trim($form.find('[name="deadline"]').val() || '')
        };
        payload.assigned_user_ids = $form.find('[name="assigned_user_ids[]"]').val() || [];

        if (!payload.title) {
            return;
        }

        $.post(cpc_projects_ajax.ajaxurl, payload).done(function(resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.tasks_html) {
                alert(cpc_projects_ajax.updateTaskError || 'Task konnte nicht aktualisiert werden.');
                return;
            }

            replaceTaskPanel(projectId, resp.data.tasks_html);
        }).fail(function() {
            alert(cpc_projects_ajax.updateTaskError || 'Task konnte nicht aktualisiert werden.');
        });
    });

    $(document).on('submit', '.cpc_projects_task_comment_form', function(e) {
        e.preventDefault();
        if (!hasAjaxConfig()) {
            return;
        }

        var $form = $(this);
        var taskId = parseInt($form.data('task-id'), 10);
        if (!taskId) {
            return;
        }

        var $panel = $form.closest('.cpc_projects_task_panel');
        var projectId = parseInt($panel.data('project-id'), 10);
        if (!projectId) {
            return;
        }

        var comment = $.trim($form.find('[name="comment"]').val() || '');
        if (!comment) {
            return;
        }

        var formData = new window.FormData();
        formData.append('action', 'cpc_projects_add_comment');
        formData.append('nonce', cpc_projects_ajax.nonce);
        formData.append('task_id', taskId);
        formData.append('comment', comment);

        var fileInput = $form.find('[name="attachments[]"]')[0];
        if (fileInput && fileInput.files && fileInput.files.length) {
            $.each(fileInput.files, function(i, file) {
                formData.append('attachments[]', file);
            });
        }

        $.ajax({
            url: cpc_projects_ajax.ajaxurl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).done(function(resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.tasks_html) {
                alert(cpc_projects_ajax.addCommentError || 'Kommentar konnte nicht gespeichert werden.');
                return;
            }

            replaceTaskPanel(projectId, resp.data.tasks_html);
        }).fail(function() {
            alert(cpc_projects_ajax.addCommentError || 'Kommentar konnte nicht gespeichert werden.');
        });
    });

    $(document).on('keyup change', '.cpc_projects_task_filter_text, .cpc_projects_task_filter_status', function() {
        var $panel = $(this).closest('.cpc_projects_task_panel');
        applyTaskFilters($panel);
    });

    $(function() {
        $('.cpc_projects_task_panel').each(function() {
            applyTaskFilters($(this));
        });
    });
})(jQuery);
