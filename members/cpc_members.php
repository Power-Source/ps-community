<?php

if (!defined('ABSPATH')) {
    exit;
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
    // Usage: [cpc-members] or [cpc-members-directory]
    // Main attrs: per_page, role, show_search, show_atoz, show_last_active, order
    $values = function_exists('cpc_get_shortcode_options') ? cpc_get_shortcode_options('cpc_members_directory') : array();

    $atts = shortcode_atts(array(
        'per_page' => cpc_get_shortcode_value($values, 'cpc_members_directory-per_page', 24),
        'role' => cpc_get_shortcode_value($values, 'cpc_members_directory-role', ''),
        'show_search' => cpc_get_shortcode_value($values, 'cpc_members_directory-show_search', 1),
        'show_atoz' => cpc_get_shortcode_value($values, 'cpc_members_directory-show_atoz', 1),
        'show_last_active' => cpc_get_shortcode_value($values, 'cpc_members_directory-show_last_active', 1),
        'order' => cpc_get_shortcode_value($values, 'cpc_members_directory-order', 'ASC'),
        'styles' => true,
        'before' => '',
        'after' => '',
    ), $atts, 'cpc_members_directory');

    list($query_args, $paged, $per_page, $search_term, $letter) = cpc_members_build_user_query_args($atts);
    $query = new WP_User_Query($query_args);
    $users = (array)$query->get_results();

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
            if (!empty($atts['show_last_active'])) {
                $last_active = get_user_meta($user->ID, 'cpccom_last_active', true);
                $last_active_ts = $last_active ? strtotime((string)$last_active) : 0;
                if ($last_active_ts) {
                    $ago = human_time_diff($last_active_ts, current_time('timestamp'));
                    $html .= '<div class="cpc-member-last-active">' . sprintf(esc_html__('Aktiv: vor %s', CPC2_TEXT_DOMAIN), esc_html($ago)) . '</div>';
                }
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
