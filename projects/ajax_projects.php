<?php

add_action('wp_ajax_cpc_projects_get_tasks', 'cpc_projects_ajax_get_tasks');
add_action('wp_ajax_cpc_projects_add_task', 'cpc_projects_ajax_add_task');
add_action('wp_ajax_cpc_projects_toggle_task', 'cpc_projects_ajax_toggle_task');
add_action('wp_ajax_cpc_projects_delete_task', 'cpc_projects_ajax_delete_task');
add_action('wp_ajax_cpc_projects_add_comment', 'cpc_projects_ajax_add_comment');
add_action('wp_ajax_cpc_projects_update_task', 'cpc_projects_ajax_update_task');
add_action('wp_ajax_cpc_projects_delete_comment_attachment', 'cpc_projects_ajax_delete_comment_attachment');
add_action('wp_ajax_cpc_projects_update_project', 'cpc_projects_ajax_update_project');
add_action('wp_ajax_cpc_projects_delete_project', 'cpc_projects_ajax_delete_project');
add_action('wp_ajax_cpc_projects_save_notification_prefs', 'cpc_projects_ajax_save_notification_prefs');
add_action('wp_ajax_cpc_projects_add_task_attachment', 'cpc_projects_ajax_add_task_attachment');
add_action('wp_ajax_cpc_projects_delete_task_attachment', 'cpc_projects_ajax_delete_task_attachment_handler');
add_action('wp_ajax_cpc_projects_delete_comment', 'cpc_projects_ajax_delete_comment');

function cpc_projects_ajax_verify() {
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Nicht angemeldet.', CPC2_TEXT_DOMAIN)), 403);
	}

	$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
	if (!wp_verify_nonce($nonce, 'cpc_projects_ajax_nonce')) {
		wp_send_json_error(array('message' => __('Ungueltige Anfrage.', CPC2_TEXT_DOMAIN)), 403);
	}
}

function cpc_projects_ajax_get_tasks() {
	cpc_projects_ajax_verify();

	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	if (!$project_id || !cpc_projects_user_can_view_project($project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	wp_send_json_success(array(
		'tasks_html' => cpc_projects_render_task_panel($project_id),
	));
}

function cpc_projects_ajax_add_task() {
	cpc_projects_ajax_verify();

	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	$title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
	$description = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';
	$priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 1;
	$deadline = isset($_POST['deadline']) ? sanitize_text_field(wp_unslash($_POST['deadline'])) : '';
	$assigned_user_ids = isset($_POST['assigned_user_ids']) ? wp_unslash($_POST['assigned_user_ids']) : array();

	if (!$project_id || !cpc_projects_user_can_manage_project($project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	if ($title === '') {
		wp_send_json_error(array('message' => __('Titel fehlt.', CPC2_TEXT_DOMAIN)), 400);
	}

	$task_id = cpc_projects_add_task($project_id, get_current_user_id(), $title, $description, $priority, $deadline, $assigned_user_ids);
	if (!$task_id) {
		wp_send_json_error(array('message' => __('Task konnte nicht erstellt werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	if (!empty($_FILES['task_attachments'])) {
		cpc_projects_add_task_attachments($project_id, $task_id, $_FILES['task_attachments']);
	}

	$task = cpc_projects_get_task($task_id);
	if ($task) {
		cpc_projects_notify_task_event($project_id, $task, 'created', get_current_user_id());
	}

	wp_send_json_success(array(
		'task_id' => $task_id,
		'tasks_html' => cpc_projects_render_task_panel($project_id),
	));
}

function cpc_projects_ajax_update_task() {
	cpc_projects_ajax_verify();

	$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
	$title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
	$description = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';
	$priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 1;
	$deadline = isset($_POST['deadline']) ? sanitize_text_field(wp_unslash($_POST['deadline'])) : '';
	$assigned_user_ids = isset($_POST['assigned_user_ids']) ? wp_unslash($_POST['assigned_user_ids']) : array();

	$task = cpc_projects_get_task($task_id);
	if (!$task || !cpc_projects_user_can_manage_project((int)$task->project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	if ($title === '') {
		wp_send_json_error(array('message' => __('Titel fehlt.', CPC2_TEXT_DOMAIN)), 400);
	}

	$updated = cpc_projects_update_task($task_id, array(
		'title' => $title,
		'description' => $description,
		'priority' => $priority,
		'deadline' => $deadline,
		'assigned_user_ids' => $assigned_user_ids,
	));

	if (!$updated) {
		wp_send_json_error(array('message' => __('Task konnte nicht aktualisiert werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	if (!empty($_FILES['task_attachments'])) {
		cpc_projects_add_task_attachments((int)$task->project_id, $task_id, $_FILES['task_attachments']);
	}

	$updated_task = cpc_projects_get_task($task_id);
	if ($updated_task) {
		cpc_projects_notify_task_event((int)$task->project_id, $updated_task, 'updated', get_current_user_id());
	}

	wp_send_json_success(array(
		'tasks_html' => cpc_projects_render_task_panel((int)$task->project_id),
	));
}

function cpc_projects_ajax_toggle_task() {
	cpc_projects_ajax_verify();

	$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
	$status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'open';
	$task = cpc_projects_get_task($task_id);

	if (!$task || !cpc_projects_user_can_manage_project((int)$task->project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	if (!in_array($status, array('open', 'done'), true)) {
		$status = 'open';
	}

	$old_status = (string)$task->status;

	if (!cpc_projects_update_task_status($task_id, $status, get_current_user_id())) {
		wp_send_json_error(array('message' => __('Task konnte nicht aktualisiert werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	$updated_task = cpc_projects_get_task($task_id);
	if ($updated_task && $old_status !== $status) {
		$event_type = ($status === 'done') ? 'completed' : 'reopened';
		cpc_projects_notify_task_event((int)$task->project_id, $updated_task, $event_type, get_current_user_id());
	}

	wp_send_json_success(array(
		'tasks_html' => cpc_projects_render_task_panel((int)$task->project_id),
	));
}

function cpc_projects_ajax_delete_task() {
	cpc_projects_ajax_verify();

	$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
	$task = cpc_projects_get_task($task_id);

	if (!$task || !cpc_projects_user_can_manage_project((int)$task->project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	$task_snapshot = clone $task;

	if (!cpc_projects_delete_task($task_id)) {
		wp_send_json_error(array('message' => __('Task konnte nicht geloescht werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	cpc_projects_notify_task_event((int)$task->project_id, $task_snapshot, 'deleted', get_current_user_id());

	wp_send_json_success(array(
		'tasks_html' => cpc_projects_render_task_panel((int)$task->project_id),
	));
}

function cpc_projects_ajax_add_comment() {
	cpc_projects_ajax_verify();

	$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
	$content = isset($_POST['comment']) ? wp_kses_post(wp_unslash($_POST['comment'])) : '';
	$task = cpc_projects_get_task($task_id);

	if (!$task || !cpc_projects_user_can_view_project((int)$task->project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	if (trim((string)$content) === '') {
		wp_send_json_error(array('message' => __('Kommentar fehlt.', CPC2_TEXT_DOMAIN)), 400);
	}

	$comment_id = cpc_projects_add_task_comment((int)$task->project_id, $task_id, get_current_user_id(), $content);
	if (!$comment_id) {
		wp_send_json_error(array('message' => __('Kommentar konnte nicht gespeichert werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	$attachment_ids = array();
	if (!empty($_FILES['attachments'])) {
		$attachment_ids = cpc_projects_add_comment_attachments((int)$task->project_id, $task_id, $comment_id, $_FILES['attachments']);
	}

	cpc_projects_notify_task_event((int)$task->project_id, $task, 'commented', get_current_user_id(), $content);

	wp_send_json_success(array(
		'comment_id' => (int)$comment_id,
		'attachment_ids' => $attachment_ids,
		'tasks_html' => cpc_projects_render_task_panel((int)$task->project_id),
	));
}

function cpc_projects_ajax_delete_comment_attachment() {
	cpc_projects_ajax_verify();

	$attachment_id = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;
	if ($attachment_id <= 0) {
		wp_send_json_error(array('message' => __('Ungueltige Datei.', CPC2_TEXT_DOMAIN)), 400);
	}

	$project_id = (int)get_post_meta($attachment_id, 'cpc_project_id', true);
	if ($project_id <= 0 || !cpc_projects_user_can_view_project($project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	if (!cpc_projects_delete_comment_attachment($attachment_id, get_current_user_id())) {
		wp_send_json_error(array('message' => __('Datei konnte nicht geloescht werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	wp_send_json_success(array(
		'tasks_html' => cpc_projects_render_task_panel($project_id),
	));
}

function cpc_projects_ajax_add_task_attachment() {
	cpc_projects_ajax_verify();

	$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
	$task    = cpc_projects_get_task($task_id);

	if (!$task || !cpc_projects_user_can_manage_project((int)$task->project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	if (empty($_FILES['task_attachments'])) {
		wp_send_json_error(array('message' => __('Keine Datei gesendet.', CPC2_TEXT_DOMAIN)), 400);
	}

	$ids = cpc_projects_add_task_attachments((int)$task->project_id, $task_id, $_FILES['task_attachments']);

	wp_send_json_success(array(
		'attachment_ids' => $ids,
		'tasks_html'     => cpc_projects_render_task_panel((int)$task->project_id),
	));
}

function cpc_projects_ajax_delete_task_attachment_handler() {
	cpc_projects_ajax_verify();

	$attachment_id = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;
	if ($attachment_id <= 0) {
		wp_send_json_error(array('message' => __('Ungueltige Datei.', CPC2_TEXT_DOMAIN)), 400);
	}

	$project_id = (int)get_post_meta($attachment_id, 'cpc_project_id', true);
	if ($project_id <= 0 || !cpc_projects_user_can_view_project($project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	if (!cpc_projects_delete_task_attachment($attachment_id, get_current_user_id())) {
		wp_send_json_error(array('message' => __('Datei konnte nicht geloescht werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	wp_send_json_success(array(
		'tasks_html' => cpc_projects_render_task_panel($project_id),
	));
}

function cpc_projects_ajax_delete_comment() {
	cpc_projects_ajax_verify();

	$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
	$comment    = get_comment($comment_id);

	if (!$comment || $comment->comment_type !== 'cpc_project_task_comment') {
		wp_send_json_error(array('message' => __('Kommentar nicht gefunden.', CPC2_TEXT_DOMAIN)), 404);
	}

	$project_id = (int)$comment->comment_post_ID;
	if (!cpc_projects_user_can_view_project($project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	if (!cpc_projects_delete_task_comment($comment_id, get_current_user_id())) {
		wp_send_json_error(array('message' => __('Kommentar konnte nicht geloescht werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	wp_send_json_success(array(
		'tasks_html' => cpc_projects_render_task_panel($project_id),
	));
}

function cpc_projects_ajax_update_project() {
	cpc_projects_ajax_verify();

	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	if (!$project_id || !cpc_projects_user_can_manage_project($project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	$title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
	$description = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';
	$status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'public';

	if (!in_array($status, array('public', 'members', 'private'), true)) {
		$status = 'public';
	}

	if ($title === '') {
		wp_send_json_error(array('message' => __('Titel fehlt.', CPC2_TEXT_DOMAIN)), 400);
	}

	$result = wp_update_post(array(
		'ID' => $project_id,
		'post_title' => $title,
		'post_content' => $description,
	), true);

	if (is_wp_error($result)) {
		wp_send_json_error(array('message' => __('Projekt konnte nicht aktualisiert werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	update_post_meta($project_id, 'cpc_project_status', $status);

	wp_send_json_success(array(
		'message' => __('Projekt wurde aktualisiert.', CPC2_TEXT_DOMAIN),
		'title' => get_the_title($project_id),
	));
}

function cpc_projects_ajax_delete_project() {
	cpc_projects_ajax_verify();

	$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
	if (!$project_id || !cpc_projects_user_can_manage_project($project_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
	}

	$redirect_url = home_url('/');
	$component = cpc_projects_get_component($project_id);
	$component_id = cpc_projects_get_component_id($project_id);
	if ($component === 'members' && $component_id > 0) {
		$redirect_url = get_author_posts_url($component_id);
	}

	$deleted = wp_delete_post($project_id, true);
	if (!$deleted) {
		wp_send_json_error(array('message' => __('Projekt konnte nicht geloescht werden.', CPC2_TEXT_DOMAIN)), 500);
	}

	wp_send_json_success(array(
		'message' => __('Projekt wurde geloescht.', CPC2_TEXT_DOMAIN),
		'redirect' => $redirect_url,
	));
}

function cpc_projects_ajax_save_notification_prefs() {
	cpc_projects_ajax_verify();

	$user_id = get_current_user_id();
	if (!$user_id) {
		wp_send_json_error(array('message' => __('Nicht angemeldet.', CPC2_TEXT_DOMAIN)), 403);
	}

	$notify_task    = isset($_POST['notify_task'])    ? (int)(bool)$_POST['notify_task']    : 0;
	$notify_comment = isset($_POST['notify_comment']) ? (int)(bool)$_POST['notify_comment'] : 0;

	update_user_meta($user_id, 'cpc_projects_notify_task',    $notify_task);
	update_user_meta($user_id, 'cpc_projects_notify_comment', $notify_comment);

	wp_send_json_success(array(
		'message' => __('Einstellungen gespeichert.', CPC2_TEXT_DOMAIN),
	));
}
