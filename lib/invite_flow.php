<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpc_invite_safe_redirect($url) {
    $default = home_url('/');
    $url = trim((string)$url);
    if ($url === '') {
        return $default;
    }

    $validated = wp_validate_redirect($url, $default);
    if (!$validated) {
        return $default;
    }

    $home_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
    $target_host = wp_parse_url($validated, PHP_URL_HOST);
    if ($home_host && $target_host && strtolower($home_host) !== strtolower($target_host)) {
        return $default;
    }

    $allowed = (array)apply_filters('cpc_invite_allowed_redirects', array(
        home_url('/'),
        get_permalink((int)get_option('cpccom_profile_page')),
        get_permalink((int)get_option('cpccom_group_single_page')),
        get_permalink((int)get_option('cpccom_groups_page')),
    ));

    $allowed = array_filter(array_map('strval', $allowed));
    if (empty($allowed)) {
        return $default;
    }

    foreach ($allowed as $allowed_url) {
        if ($allowed_url && strpos($validated, $allowed_url) === 0) {
            return $validated;
        }
    }

    return $default;
}

function cpc_invite_token_key($token) {
    return 'cpc_invite_' . md5((string)$token);
}

function cpc_invite_generate_token() {
    try {
        return bin2hex(random_bytes(16));
    } catch (Exception $e) {
        return wp_generate_password(32, false, false);
    }
}

function cpc_invite_send_email($email, $token, $redirect_url, $inviter_id) {
    $invite_link = add_query_arg('cpc_invite', rawurlencode($token), home_url('/'));
    $inviter = get_user_by('id', (int)$inviter_id);
    $inviter_name = $inviter ? $inviter->display_name : __('Ein Mitglied', CPC2_TEXT_DOMAIN);

    $subject = sprintf(__('%s hat Dich in die Community eingeladen', CPC2_TEXT_DOMAIN), $inviter_name);
    $message = '';
    $message .= sprintf(__('Hallo! %s hat Dich eingeladen.', CPC2_TEXT_DOMAIN), $inviter_name) . "\n\n";
    $message .= __('Öffne diesen Link, um Einladung und Weiterleitung sicher zu übernehmen:', CPC2_TEXT_DOMAIN) . "\n";
    $message .= $invite_link . "\n\n";
    $message .= __('Nach dem Login wirst Du automatisch an das Ziel weitergeleitet.', CPC2_TEXT_DOMAIN) . "\n";
    $message .= __('Geplantes Ziel:', CPC2_TEXT_DOMAIN) . ' ' . cpc_invite_safe_redirect($redirect_url) . "\n";

    return wp_mail($email, $subject, $message);
}

function cpc_invite_handle_submission() {
    if (empty($_POST['cpc_invite_submit'])) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    $nonce = isset($_POST['cpc_invite_nonce']) ? sanitize_text_field(wp_unslash($_POST['cpc_invite_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'cpc_invite_submit')) {
        return;
    }

    $email = isset($_POST['cpc_invite_email']) ? sanitize_email(wp_unslash($_POST['cpc_invite_email'])) : '';
    $redirect = isset($_POST['cpc_invite_redirect']) ? esc_url_raw(wp_unslash($_POST['cpc_invite_redirect'])) : home_url('/');

    if (!is_email($email)) {
        $target = add_query_arg('cpc_invite_status', 'invalid_email', wp_get_referer() ? wp_get_referer() : home_url('/'));
        wp_safe_redirect($target);
        exit;
    }

    $token = cpc_invite_generate_token();
    $payload = array(
        'email' => strtolower($email),
        'redirect' => cpc_invite_safe_redirect($redirect),
        'inviter_id' => get_current_user_id(),
        'created' => time(),
        'blog_id' => get_current_blog_id(),
    );

    set_transient(cpc_invite_token_key($token), $payload, DAY_IN_SECONDS * 7);

    $sent = cpc_invite_send_email($email, $token, $payload['redirect'], $payload['inviter_id']);
    $status = $sent ? 'sent' : 'send_failed';

    $target = add_query_arg('cpc_invite_status', $status, wp_get_referer() ? wp_get_referer() : home_url('/'));
    wp_safe_redirect($target);
    exit;
}
add_action('init', 'cpc_invite_handle_submission');

function cpc_invite_handle_token() {
    if (!isset($_GET['cpc_invite'])) {
        return;
    }

    $token = sanitize_text_field(wp_unslash($_GET['cpc_invite']));
    if ($token === '') {
        return;
    }

    $payload = get_transient(cpc_invite_token_key($token));
    if (!is_array($payload)) {
        wp_die(esc_html__('Der Einladungslink ist ungültig oder abgelaufen.', CPC2_TEXT_DOMAIN));
    }

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $expected_email = isset($payload['email']) ? strtolower((string)$payload['email']) : '';
        $current_email = strtolower((string)$current_user->user_email);

        if ($expected_email !== '' && $expected_email !== $current_email && !current_user_can('manage_options')) {
            wp_die(esc_html__('Diese Einladung ist für eine andere E-Mail-Adresse vorgesehen.', CPC2_TEXT_DOMAIN));
        }

        delete_transient(cpc_invite_token_key($token));

        $target = cpc_invite_safe_redirect(isset($payload['redirect']) ? $payload['redirect'] : home_url('/'));
        wp_safe_redirect($target);
        exit;
    }

    $return_url = add_query_arg('cpc_invite', rawurlencode($token), home_url('/'));
    $login_url = wp_login_url($return_url);
    $register_url = add_query_arg('redirect_to', rawurlencode($return_url), wp_registration_url());

    $html = '<div style="max-width:680px;margin:40px auto;padding:20px;border:1px solid #ddd;background:#fff">';
    $html .= '<h2>' . esc_html__('Einladung erkannt', CPC2_TEXT_DOMAIN) . '</h2>';
    $html .= '<p>' . esc_html__('Bitte melde Dich an oder registriere Dich. Danach wirst Du automatisch weitergeleitet.', CPC2_TEXT_DOMAIN) . '</p>';
    $html .= '<p><a class="button button-primary" href="' . esc_url($login_url) . '">' . esc_html__('Anmelden', CPC2_TEXT_DOMAIN) . '</a> ';
    $html .= '<a class="button" href="' . esc_url($register_url) . '">' . esc_html__('Registrieren', CPC2_TEXT_DOMAIN) . '</a></p>';
    $html .= '</div>';

    wp_die($html);
}
add_action('template_redirect', 'cpc_invite_handle_token', 1);

function cpc_invite_shortcode($atts) {
    // Usage: [cpc-invite redirect="https://example.com/path/"]
    if (!is_user_logged_in()) {
        return '<div class="cpc-invite-login">' . esc_html__('Bitte logge Dich ein, um Einladungen zu versenden.', CPC2_TEXT_DOMAIN) . '</div>';
    }

    $atts = shortcode_atts(array(
        'redirect' => home_url('/'),
    ), $atts, 'cpc-invite');

    $status = isset($_GET['cpc_invite_status']) ? sanitize_key(wp_unslash($_GET['cpc_invite_status'])) : '';
    $notice = '';

    if ($status === 'sent') {
        $notice = '<div class="cpc_success">' . esc_html__('Einladung wurde verschickt.', CPC2_TEXT_DOMAIN) . '</div>';
    } elseif ($status === 'send_failed') {
        $notice = '<div class="cpc_error">' . esc_html__('Einladung konnte nicht versendet werden.', CPC2_TEXT_DOMAIN) . '</div>';
    } elseif ($status === 'invalid_email') {
        $notice = '<div class="cpc_error">' . esc_html__('Bitte gib eine gültige E-Mail-Adresse ein.', CPC2_TEXT_DOMAIN) . '</div>';
    }

    $default_redirect = cpc_invite_safe_redirect($atts['redirect']);

    $html = '<div class="cpc-invite-form-wrap">';
    $html .= $notice;
    $html .= '<form method="post" class="cpc-invite-form">';
    $html .= wp_nonce_field('cpc_invite_submit', 'cpc_invite_nonce', true, false);
    $html .= '<p><label>' . esc_html__('E-Mail-Adresse', CPC2_TEXT_DOMAIN) . '<br>';
    $html .= '<input type="email" name="cpc_invite_email" required></label></p>';
    $html .= '<p><label>' . esc_html__('Weiterleitungsziel (gleiches Netzwerk)', CPC2_TEXT_DOMAIN) . '<br>';
    $html .= '<input type="url" name="cpc_invite_redirect" value="' . esc_attr($default_redirect) . '"></label></p>';
    $html .= '<p><button type="submit" name="cpc_invite_submit" value="1">' . esc_html__('Einladung senden', CPC2_TEXT_DOMAIN) . '</button></p>';
    $html .= '</form>';
    $html .= '</div>';

    return $html;
}

if (!is_admin()) {
    add_shortcode('cpc-invite', 'cpc_invite_shortcode');
}
