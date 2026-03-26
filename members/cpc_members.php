<?php

if (!defined('ABSPATH')) {
    exit;
}

if (is_admin()) {
    add_action('cpc_admin_getting_started_core_hook', 'cpc_admin_getting_started_members_core_option', 20);
    add_action('cpc_admin_getting_started_core_save_hook', 'cpc_admin_members_save', 10, 1);
}

function cpc_members_is_core_enabled() {
    $core = get_option('cpc_default_core', '');
    return is_string($core) && strpos($core, 'core-members') !== false;
}

function cpc_admin_getting_started_members_core_option() {
    if (!cpc_members_is_core_enabled()) {
        return;
    }

    echo '<tr class="form-field">';
    echo '<th scope="row" valign="top"><label>'.__('Mitgliederverzeichnis-Seite', CPC2_TEXT_DOMAIN).'</label></th>';
    echo '<td>';
    echo wp_dropdown_pages(array(
        'name' => 'cpc_members_directory_page',
        'echo' => 0,
        'show_option_none' => __('Keine feste Seite', CPC2_TEXT_DOMAIN),
        'option_none_value' => '0',
        'selected' => cpc_members_get_directory_page_id(),
    ));
    echo '<br /><span class="description">'.__('Wähle eine bestehende Seite mit [cpc-members-directory] oder erstelle unten automatisch eine neue Seite mit eingefügtem Shortcode.', CPC2_TEXT_DOMAIN).'</span>';
    echo '<br /><label><input type="checkbox" style="width:10px" name="cpc_members_directory_create_page" /> '.__('Mitglieder-Seite automatisch erstellen (inkl. [cpc-members-directory]).', CPC2_TEXT_DOMAIN).'</label>';
    echo '</td>';
    echo '</tr>';
}

function cpc_admin_members_save($the_post) {
    if (isset($the_post['cpc_members_directory_page'])) {
        update_option('cpc_members_directory_page', max(0, (int)$the_post['cpc_members_directory_page']));
    }

    if (isset($the_post['cpc_members_directory_create_page']) && function_exists('cpc_admin_create_standard_page')) {
        $new_page_id = cpc_admin_create_standard_page('cpc_members_directory_page', array(
            'post_name' => sanitize_title(__('mitglieder', CPC2_TEXT_DOMAIN)),
            'post_title' => __('Mitglieder', CPC2_TEXT_DOMAIN),
            'post_content' => '[cpc-members-directory]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
        ), 'cpc-members-directory');

        if (!is_wp_error($new_page_id) && $new_page_id) {
            update_option('cpc_members_directory_page', (int)$new_page_id);
        }
    }
}

function cpc_members_get_directory_page_id() {
    return max(0, (int)get_option('cpc_members_directory_page', 0));
}

function cpc_members_get_current_letter() {
    $letter = isset($_GET['letter']) ? strtoupper(sanitize_text_field(wp_unslash($_GET['letter']))) : '';
    if ($letter !== '' && preg_match('/^[A-Z]$/', $letter)) {
        return $letter;
    }
    return '';
}

function cpc_members_get_profile_url($user_id) {
    if (function_exists('cpc_comfile_link')) {
        return cpc_comfile_link((int)$user_id);
    }

    $profile_page_id = (int)get_option('cpccom_profile_page');
    if ($profile_page_id > 0) {
        $url = get_permalink($profile_page_id);
        if ($url) {
            return add_query_arg('user_id', (int)$user_id, $url);
        }
    }

    return get_author_posts_url((int)$user_id);
}

function cpc_members_get_user_role_label($user_id) {
    $userdata = get_userdata((int)$user_id);
    if (!$userdata || empty($userdata->roles) || !is_array($userdata->roles)) {
        return '';
    }

    $role_key = reset($userdata->roles);
    if (!$role_key) {
        return '';
    }

    $wp_roles = wp_roles();
    if ($wp_roles && isset($wp_roles->roles[$role_key]['name'])) {
        return (string)$wp_roles->roles[$role_key]['name'];
    }

    return (string)$role_key;
}

function cpc_members_render_chat_status($user_id) {
    if (!function_exists('cpc_pschat_profile_status_enabled') || !cpc_pschat_profile_status_enabled() || !function_exists('psource_chat_get_user_status')) {
        return '';
    }

    $status_key = (string)psource_chat_get_user_status((int)$user_id);
    if ($status_key === '') {
        return '';
    }

    $status_options = function_exists('cpc_pschat_get_status_options') ? (array)cpc_pschat_get_status_options() : array();
    $status_label = isset($status_options[$status_key]) ? (string)$status_options[$status_key] : $status_key;

    return '<div class="cpc-member-chat-status cpc-pschat-status-'.esc_attr(sanitize_html_class($status_key)).'">'.esc_html__('Chat:', CPC2_TEXT_DOMAIN).' '.esc_html($status_label).'</div>';
}

function cpc_members_render_pm_action($target_user) {
    if (!is_user_logged_in() || !is_object($target_user) || !isset($target_user->ID) || (int)$target_user->ID <= 0) {
        return '';
    }

    if (!function_exists('cpc_pm_integration_enabled') || !cpc_pm_integration_enabled()) {
        return '';
    }

    if ((int)get_current_user_id() === (int)$target_user->ID) {
        return '';
    }

    $login = isset($target_user->user_login) ? sanitize_user((string)$target_user->user_login, true) : '';
    $shortcode_candidates = array(
        array('tag' => 'message_button', 'atts' => array('user_id' => (int)$target_user->ID)),
        array('tag' => 'message_button', 'atts' => array('to' => $login)),
        array('tag' => 'pm_button', 'atts' => array('user_id' => (int)$target_user->ID)),
        array('tag' => 'pm_button', 'atts' => array('to' => $login)),
        array('tag' => 'message_new', 'atts' => array('to' => $login)),
    );

    foreach ($shortcode_candidates as $candidate) {
        if (!shortcode_exists($candidate['tag'])) {
            continue;
        }

        $parts = array();
        foreach ($candidate['atts'] as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $parts[] = $key.'="'.esc_attr((string)$value).'"';
        }

        $shortcode = '['.$candidate['tag'].(empty($parts) ? '' : ' '.implode(' ', $parts)).']';
        $output = do_shortcode($shortcode);
        if (trim(wp_strip_all_tags((string)$output)) !== '') {
            return '<div class="cpc-member-action-pm">'.$output.'</div>';
        }
    }

    return '';
}

function cpc_members_render_member_actions($target_user, $profile_url) {
    if (!is_object($target_user) || !isset($target_user->ID)) {
        return '';
    }

    $actions = '<div class="cpc-member-actions">';
    $actions .= '<a class="cpc_button cpc-member-action-profile" href="'.esc_url($profile_url).'">'.esc_html__('Profil', CPC2_TEXT_DOMAIN).'</a>';

    if (function_exists('cpc_friends_add_button') && is_user_logged_in() && (int)get_current_user_id() !== (int)$target_user->ID) {
        $actions .= '<div class="cpc-member-action-friend">';
        $actions .= cpc_friends_add_button(array(
            'user_id' => (int)$target_user->ID,
            'show_request_message' => 0,
            'styles' => false,
            'before' => '',
            'after' => '',
        ));
        $actions .= '</div>';
    }

    $actions .= cpc_members_render_pm_action($target_user);

    $actions .= apply_filters('cpc_members_card_actions_html', '', (int)$target_user->ID, (int)get_current_user_id(), $target_user);
    $actions .= '</div>';

    return $actions;
}

function cpc_members_build_user_query_args($atts) {
    $paged = isset($_GET['cpc_members_page']) ? max(1, (int)$_GET['cpc_members_page']) : 1;
    $per_page = max(5, min(100, (int)$atts['per_page']));

    $search_term = isset($_GET['member']) ? sanitize_text_field(wp_unslash($_GET['member'])) : '';
    $letter = cpc_members_get_current_letter();

    $args = array(
        'number'  => $per_page,
        'paged'   => $paged,
        'orderby' => 'display_name',
        'order'   => strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC',
        'fields'  => array('ID', 'display_name', 'user_login', 'user_email'),
        'count_total' => true,
    );

    if (!empty($atts['role'])) {
        $args['role'] = sanitize_key($atts['role']);
    }

    if ($search_term !== '') {
        $wild = '*' . $search_term . '*';
        $args['search'] = $wild;
        $args['search_columns'] = array('display_name', 'user_login', 'user_email');
    }

    if ($letter !== '') {
        $args['search'] = $letter . '*';
        $args['search_columns'] = array('display_name', 'user_login');
    }

    return array($args, $paged, $per_page, $search_term, $letter);
}

function cpc_members_render_atoz($current_letter) {
    $html = '<div class="cpc-members-atoz">';
    foreach (range('A', 'Z') as $letter) {
        $url = add_query_arg(array('letter' => $letter, 'cpc_members_page' => 1));
        if ($letter === $current_letter) {
            $html .= '<strong>' . esc_html($letter) . '</strong> ';
        } else {
            $html .= '<a href="' . esc_url($url) . '">' . esc_html($letter) . '</a> ';
        }
    }
    $reset_url = remove_query_arg(array('letter', 'cpc_members_page'));
    $html .= '<a class="cpc-members-reset" href="' . esc_url($reset_url) . '">' . esc_html__('Alle', CPC2_TEXT_DOMAIN) . '</a>';
    $html .= '</div>';

    return $html;
}

function cpc_members_render_pagination($current, $total_pages) {
    if ($total_pages < 2) {
        return '';
    }

    $html = '<nav class="cpc-members-pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = add_query_arg('cpc_members_page', $i);
        if ($i === $current) {
            $html .= '<strong>' . (int)$i . '</strong> ';
        } else {
            $html .= '<a href="' . esc_url($url) . '">' . (int)$i . '</a> ';
        }
    }
    $html .= '</nav>';

    return $html;
}

function cpc_members_directory($atts) {
    wp_enqueue_style('cpc-members-css', plugins_url('cpc_members.css', __FILE__), array(), '1.0.0');

    // Usage: [cpc-members] or [cpc-members-directory]
    // Main attrs: per_page, role, show_search, show_atoz, show_last_active, order
    $values = function_exists('cpc_get_shortcode_options') ? cpc_get_shortcode_options('cpc_members_directory') : array();

    $atts = shortcode_atts(array(
        'per_page' => cpc_get_shortcode_value($values, 'cpc_members_directory-per_page', 24),
        'role' => cpc_get_shortcode_value($values, 'cpc_members_directory-role', ''),
        'show_search' => cpc_get_shortcode_value($values, 'cpc_members_directory-show_search', 1),
        'show_atoz' => cpc_get_shortcode_value($values, 'cpc_members_directory-show_atoz', 1),
        'show_last_active' => cpc_get_shortcode_value($values, 'cpc_members_directory-show_last_active', 1),
        'show_actions' => cpc_get_shortcode_value($values, 'cpc_members_directory-show_actions', 1),
        'order' => cpc_get_shortcode_value($values, 'cpc_members_directory-order', 'ASC'),
        'styles' => true,
        'before' => '',
        'after' => '',
    ), $atts, 'cpc_members_directory');

    list($query_args, $paged, $per_page, $search_term, $letter) = cpc_members_build_user_query_args($atts);
    $query = new WP_User_Query($query_args);
    $seen_ids = array();
    $users = array_filter((array)$query->get_results(), function($user) use (&$seen_ids) {
        if (in_array($user->ID, $seen_ids, true)) {
            return false;
        }
        $seen_ids[] = $user->ID;
        return true;
    });

    $total = (int)$query->get_total();
    $total_pages = (int)ceil($total / $per_page);

    $html = '<div class="cpc-members-directory">';

    if (!empty($atts['show_search'])) {
        $html .= '<form method="get" class="cpc-members-search" action="">';
        foreach ($_GET as $key => $value) {
            if (in_array($key, array('member', 'cpc_members_page'), true)) {
                continue;
            }
            if (is_scalar($value)) {
                $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr(wp_unslash($value)) . '">';
            }
        }
        $html .= '<input type="text" name="member" value="' . esc_attr($search_term) . '" placeholder="' . esc_attr__('Mitglied suchen...', CPC2_TEXT_DOMAIN) . '">';
        $html .= '<button type="submit">' . esc_html__('Suche', CPC2_TEXT_DOMAIN) . '</button>';
        $html .= '</form>';
    }

    if (!empty($atts['show_atoz'])) {
        $html .= cpc_members_render_atoz($letter);
    }

    $html .= '<div class="cpc-members-summary">' . sprintf(esc_html__('%d Mitglieder gefunden', CPC2_TEXT_DOMAIN), $total) . '</div>';

    if (empty($users)) {
        $html .= '<p>' . esc_html__('Keine Mitglieder gefunden.', CPC2_TEXT_DOMAIN) . '</p>';
    } else {
        $html .= '<div class="cpc-members-grid">';
        foreach ($users as $user) {
            $profile_url = cpc_members_get_profile_url($user->ID);
            $html .= '<article class="cpc-member-card">';
            $html .= '<a class="cpc-member-avatar" href="' . esc_url($profile_url) . '">' . get_avatar($user->ID, 64) . '</a>';
            $html .= '<div class="cpc-member-meta">';
            $html .= '<a class="cpc-member-name" href="' . esc_url($profile_url) . '">' . esc_html($user->display_name) . '</a>';
            $role_label = cpc_members_get_user_role_label($user->ID);
            if ($role_label !== '') {
                $html .= '<div class="cpc-member-role">' . esc_html($role_label) . '</div>';
            }
            $html .= cpc_members_render_chat_status($user->ID);
            if (!empty($atts['show_last_active'])) {
                $last_active = get_user_meta($user->ID, 'cpccom_last_active', true);
                $last_active_ts = $last_active ? strtotime((string)$last_active) : 0;
                if ($last_active_ts) {
                    $ago = human_time_diff($last_active_ts, current_time('timestamp'));
                    $html .= '<div class="cpc-member-last-active">' . sprintf(esc_html__('Aktiv: vor %s', CPC2_TEXT_DOMAIN), esc_html($ago)) . '</div>';
                }
            }
            if (!empty($atts['show_actions'])) {
                $html .= cpc_members_render_member_actions($user, $profile_url);
            }
            $html .= '</div>';
            $html .= '</article>';
        }
        $html .= '</div>';
    }

    $html .= cpc_members_render_pagination($paged, $total_pages);
    $html .= '</div>';

    if ($html && function_exists('cpc_wrap_shortcode_styles')) {
        $html = apply_filters('cpc_wrap_shortcode_styles_filter', $html, 'cpc_members_directory', $atts['before'], $atts['after'], $atts['styles'], $values);
    }

    return $html;
}

if (!is_admin()) {
    add_shortcode(CPC_PREFIX . '-members', 'cpc_members_directory');
    add_shortcode(CPC_PREFIX . '-members-directory', 'cpc_members_directory');
}
