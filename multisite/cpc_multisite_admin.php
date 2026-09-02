<?php

if (!function_exists('cpc_multisite_get_module_registry')) {
    function cpc_multisite_get_module_registry() {
        return array(
            'core-profile' => __('Profil', 'cp-community'),
            'core-activity' => __('Aktivität', 'cp-community'),
            'core-avatar' => __('Avatar', 'cp-community'),
            'core-friendships' => __('Freundschaften', 'cp-community'),
            'core-alerts' => __('Benachrichtigungen', 'cp-community'),
            'core-forums' => __('Foren', 'cp-community'),
            'core-groups' => __('Gruppen', 'cp-community'),
            'core-members' => __('Mitgliederverzeichnis', 'cp-community'),
            'core-media' => __('Medien', 'cp-community'),
            'core-docs' => __('Dokumente', 'cp-community'),
            'core-projects' => __('Projekte', 'cp-community'),
            'core-invite' => __('Einladungen', 'cp-community'),
        );
    }

    function cpc_multisite_parse_module_list($modules) {
        if (is_string($modules)) {
            $modules = explode(',', $modules);
        }

        if (!is_array($modules)) {
            return array();
        }

        $modules = array_map('sanitize_key', $modules);
        $modules = array_filter($modules);

        return array_values(array_unique($modules));
    }

    function cpc_multisite_is_main_blog($blog_id = null) {
        if (!is_multisite()) {
            return true;
        }

        $blog_id = $blog_id !== null ? (int)$blog_id : (int)get_current_blog_id();
        if (function_exists('get_main_site_id')) {
            return $blog_id === (int)get_main_site_id();
        }

        return $blog_id === 1;
    }

    function cpc_multisite_get_module_scopes() {
        if (!is_multisite()) {
            return array();
        }

        $normalized = array();
        foreach (cpc_multisite_get_module_registry() as $module => $label) {
            $normalized[$module] = cpc_multisite_is_module_allowed_for_subsites($module) ? 'network' : 'site';
        }

        return $normalized;
    }

    function cpc_multisite_get_network_active_modules() {
        if (!is_multisite()) {
            return array();
        }

        return cpc_multisite_parse_module_list(get_site_option('cpc_multisite_network_active_modules', array()));
    }

    function cpc_multisite_is_bloghosting_active() {
        return is_multisite() && (function_exists('is_pro_site') || (isset($GLOBALS['psts']) && is_object($GLOBALS['psts'])) || class_exists('ProSites'));
    }

    function cpc_multisite_get_bloghosting_levels() {
        if (!cpc_multisite_is_bloghosting_active()) {
            return array();
        }

        $levels = get_site_option('psts_levels', array());
        if (!is_array($levels)) {
            return array();
        }

        $normalized = array();
        foreach ($levels as $level_id => $level_data) {
            $level_id = (int)$level_id;
            if ($level_id <= 0) {
                continue;
            }

            $name = is_array($level_data) && !empty($level_data['name']) ? sanitize_text_field($level_data['name']) : sprintf(__('Level %d', 'cp-community'), $level_id);
            $normalized[$level_id] = array(
                'id' => $level_id,
                'name' => $name,
            );
        }

        ksort($normalized);
        return $normalized;
    }

    function cpc_multisite_get_bloghosting_level_allowed_modules_map() {
        if (!cpc_multisite_is_bloghosting_active()) {
            return array();
        }

        $raw = get_site_option('cpc_multisite_bloghosting_level_modules', array());
        if (!is_array($raw)) {
            return array();
        }

        $map = array();
        foreach ($raw as $level_id => $modules) {
            $level_id = (int)$level_id;
            if ($level_id <= 0) {
                continue;
            }
            $map[$level_id] = cpc_multisite_parse_module_list($modules);
        }

        return $map;
    }

    function cpc_multisite_get_current_bloghosting_level($blog_id = null) {
        if (!cpc_multisite_is_bloghosting_active()) {
            return 0;
        }

        $blog_id = $blog_id !== null ? (int)$blog_id : (int)get_current_blog_id();
        if ($blog_id <= 0 || cpc_multisite_is_main_blog($blog_id)) {
            return 0;
        }

        if (isset($GLOBALS['psts']) && is_object($GLOBALS['psts']) && method_exists($GLOBALS['psts'], 'get_level')) {
            return max(0, (int)$GLOBALS['psts']->get_level($blog_id));
        }

        return 0;
    }

    function cpc_multisite_get_allowed_modules_for_subsites_without_level() {
        if (!is_multisite()) {
            return array_keys(cpc_multisite_get_module_registry());
        }

        $allowed = cpc_multisite_get_network_active_modules();
        if ($allowed) {
            return $allowed;
        }

        return array_keys(cpc_multisite_get_module_registry());
    }

    function cpc_multisite_get_allowed_modules_for_subsites($blog_id = null, $level_override = null) {
        if (!is_multisite()) {
            return array_keys(cpc_multisite_get_module_registry());
        }

        $blog_id = $blog_id !== null ? (int)$blog_id : (int)get_current_blog_id();
        if (cpc_multisite_is_main_blog($blog_id)) {
            return array_keys(cpc_multisite_get_module_registry());
        }

        $default_allowed = cpc_multisite_get_allowed_modules_for_subsites_without_level();

        if (!cpc_multisite_is_bloghosting_active()) {
            return $default_allowed;
        }

        $level = $level_override !== null ? max(0, (int)$level_override) : cpc_multisite_get_current_bloghosting_level($blog_id);
        if ($level <= 0) {
            return $default_allowed;
        }

        $map = cpc_multisite_get_bloghosting_level_allowed_modules_map();
        if (isset($map[$level]) && is_array($map[$level])) {
            return $map[$level];
        }

        return $default_allowed;
    }

    function cpc_multisite_get_bloghosting_level_label($level, $bloghosting_levels = array()) {
        $level = max(0, (int)$level);
        if ($level <= 0) {
            return __('Kein Level', 'cp-community');
        }

        if (isset($bloghosting_levels[$level]['name']) && $bloghosting_levels[$level]['name'] !== '') {
            return sprintf(__('Level %1$d: %2$s', 'cp-community'), $level, $bloghosting_levels[$level]['name']);
        }

        return sprintf(__('Level %d', 'cp-community'), $level);
    }

    function cpc_multisite_is_module_allowed_for_subsites($module) {
        if (!is_multisite() || cpc_multisite_is_main_blog()) {
            return true;
        }

        $module = sanitize_key((string)$module);
        $allowed = cpc_multisite_get_allowed_modules_for_subsites();
        $registry = cpc_multisite_get_module_registry();

        if (!isset($registry[$module])) {
            return true;
        }

        return in_array($module, $allowed, true);
    }

    function cpc_multisite_is_module_network_managed($module) {
        if (!is_multisite() || cpc_multisite_is_main_blog()) {
            return false;
        }

        $module = sanitize_key((string)$module);
        return cpc_multisite_is_module_allowed_for_subsites($module);
    }

    function cpc_multisite_get_effective_core_plugins($local_core_plugins = '') {
        $local_modules = cpc_multisite_parse_module_list($local_core_plugins);
        if (!is_multisite() || cpc_multisite_is_main_blog()) {
            return implode(',', $local_modules);
        }

        $allowed_modules = cpc_multisite_get_allowed_modules_for_subsites();
        $registry = cpc_multisite_get_module_registry();

        $effective = array();

        foreach ($registry as $module => $label) {
            if (in_array($module, $allowed_modules, true) && in_array($module, $local_modules, true)) {
                $effective[] = $module;
            }
        }

        foreach ($local_modules as $module) {
            if (!isset($registry[$module])) {
                $effective[] = $module;
            }
        }

        return implode(',', array_values(array_unique(array_filter($effective))));
    }

    function cpc_multisite_get_user_cloud_scope() {
        if (!is_multisite()) {
            return 'site';
        }

        $scope = get_site_option('cpc_multisite_user_cloud_scope', 'network');
        return in_array($scope, array('network', 'site'), true) ? $scope : 'network';
    }

    function cpc_multisite_get_profile_media_scope() {
        if (!is_multisite()) {
            return 'network';
        }

        $scope = get_site_option('cpc_multisite_profile_media_scope', 'network');
        return in_array($scope, array('network', 'main_site'), true) ? $scope : 'network';
    }

    function cpc_multisite_get_avatar_scope() {
        if (!is_multisite()) {
            return 'inherit';
        }

        $scope = get_site_option('cpc_multisite_avatar_scope', 'inherit');
        return in_array($scope, array('inherit', 'network', 'site'), true) ? $scope : 'inherit';
    }

    function cpc_multisite_get_effective_avatar_scope() {
        $scope = cpc_multisite_get_avatar_scope();
        if ($scope === 'inherit') {
            return cpc_multisite_get_user_cloud_scope();
        }

        return $scope;
    }

    function cpc_multisite_is_profile_media_network_synced() {
        return !is_multisite() || cpc_multisite_get_profile_media_scope() === 'network';
    }

    function cpc_multisite_profile_media_visible_on_current_site() {
        if (!is_multisite() || cpc_multisite_is_main_blog()) {
            return true;
        }

        // If user cloud is site-scoped, each subsite may contain unique uploads.
        // Keep profile media visible there so users can manage their local files.
        if (cpc_multisite_get_user_cloud_scope() === 'site') {
            return true;
        }

        return cpc_multisite_is_profile_media_network_synced();
    }

    function cpc_multisite_is_user_cloud_network_scoped() {
        return is_multisite() && cpc_multisite_get_user_cloud_scope() === 'network';
    }

    function cpc_multisite_get_user_cloud_limit_mb($fallback = 50) {
        $fallback = max(0, min(10240, (int)$fallback));
        if (!cpc_multisite_is_user_cloud_network_scoped()) {
            return $fallback;
        }

        $limit = (int)get_site_option('cpc_multisite_user_cloud_limit_mb', $fallback);
        if ($limit < 0) {
            $limit = 0;
        }
        if ($limit > 10240) {
            $limit = 10240;
        }

        return $limit;
    }

    function cpc_multisite_get_user_cloud_folder_prefix($user_id) {
        $user_id = (int)$user_id;
        $user = get_user_by('id', $user_id);
        $slug = $user ? sanitize_title($user->user_login) : '';
        if ($slug === '') {
            $slug = 'user';
        }

        if (cpc_multisite_is_user_cloud_network_scoped()) {
            return $slug.'-'.$user_id;
        }

        $blog_id = is_multisite() ? get_current_blog_id() : 0;
        return 'site-'.$blog_id.'-'.$slug.'-'.$user_id;
    }

    function cpc_multisite_build_user_cloud_folder_name($user_id, $scope = null, $blog_id = null) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return 'user-0';
        }

        $scope = $scope ? sanitize_key((string)$scope) : (function_exists('cpc_multisite_get_user_cloud_scope') ? cpc_multisite_get_user_cloud_scope() : 'network');
        $scope = in_array($scope, array('network', 'site'), true) ? $scope : 'network';
        $blog_id = $blog_id !== null ? (int)$blog_id : (is_multisite() ? (int)get_current_blog_id() : 0);

        $user = get_user_by('id', $user_id);
        $slug = $user ? sanitize_title($user->user_login) : '';
        if ($slug === '') {
            $slug = 'user';
        }

        if ($scope === 'site' && is_multisite()) {
            $folder = 'site-'.$blog_id.'-'.$slug.'-'.$user_id;
        } else {
            $folder = $slug.'-'.$user_id;
        }

        $folder = apply_filters('cpc_activity_plus_user_cloud_folder_name', $folder, $user_id, $user);
        $folder = sanitize_title($folder);

        if ($folder === '') {
            $folder = ($scope === 'site' && is_multisite()) ? 'site-'.$blog_id.'-user-'.$user_id : 'user-'.$user_id;
        }

        return $folder;
    }

    function cpc_multisite_get_user_cloud_storage_base_dir() {
        return WP_CONTENT_DIR.'/cpc-pro-content/members/';
    }

    function cpc_multisite_maybe_migrate_user_cloud_folder($user_id, $scope = null, $blog_id = null) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return '';
        }

        $scope = $scope ? sanitize_key((string)$scope) : (function_exists('cpc_multisite_get_user_cloud_scope') ? cpc_multisite_get_user_cloud_scope() : 'network');
        $scope = in_array($scope, array('network', 'site'), true) ? $scope : 'network';
        $blog_id = $blog_id !== null ? (int)$blog_id : (is_multisite() ? (int)get_current_blog_id() : 0);

        $scope_key = 'cpc_activity_plus_cloud_folder_'.$scope;
        if (is_multisite()) {
            $scope_key .= '_'.$blog_id;
        }

        $stored = get_user_meta($user_id, $scope_key, true);
        if (!empty($stored) && preg_match('/^[a-z0-9\-]+$/', $stored)) {
            return $stored;
        }

        $legacy_stored = get_user_meta($user_id, 'cpc_activity_plus_cloud_folder', true);
        $target_folder = cpc_multisite_build_user_cloud_folder_name($user_id, $scope, $blog_id);
        $base_dir = trailingslashit(cpc_multisite_get_user_cloud_storage_base_dir());

        $candidate_sources = array();
        if (!empty($legacy_stored) && preg_match('/^[a-z0-9\-]+$/', $legacy_stored)) {
            $candidate_sources[] = $legacy_stored;
        }

        $opposite_scope = $scope === 'site' ? 'network' : 'site';
        $opposite_key = 'cpc_activity_plus_cloud_folder_'.$opposite_scope;
        if (is_multisite()) {
            $opposite_key .= '_'.$blog_id;
        }
        $opposite_stored = get_user_meta($user_id, $opposite_key, true);
        if (!empty($opposite_stored) && preg_match('/^[a-z0-9\-]+$/', $opposite_stored)) {
            $candidate_sources[] = $opposite_stored;
        }

        foreach ($candidate_sources as $source_folder) {
            if ($source_folder === $target_folder) {
                update_user_meta($user_id, $scope_key, $target_folder);
                return $target_folder;
            }

            $source_dir = $base_dir.$source_folder;
            $target_dir = $base_dir.$target_folder;
            if (file_exists($source_dir) && !file_exists($target_dir)) {
                wp_mkdir_p(dirname($target_dir));
                if (@rename($source_dir, $target_dir)) {
                    update_user_meta($user_id, $scope_key, $target_folder);
                    return $target_folder;
                }
            }
        }

        update_user_meta($user_id, $scope_key, $target_folder);

        return $target_folder;
    }

    function cpc_multisite_migrate_user_cloud_scope($old_scope, $new_scope) {
        if (!is_multisite()) {
            return array('migrated' => 0, 'skipped' => 0);
        }

        $old_scope = sanitize_key((string)$old_scope);
        $new_scope = sanitize_key((string)$new_scope);
        if (!in_array($old_scope, array('network', 'site'), true) || !in_array($new_scope, array('network', 'site'), true) || $old_scope === $new_scope) {
            return array('migrated' => 0, 'skipped' => 0);
        }

        global $wpdb;
        $user_ids = $wpdb->get_col("SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE 'cpc_activity_plus_cloud_folder%'");
        if (!$user_ids) {
            return array('migrated' => 0, 'skipped' => 0);
        }

        $migrated = 0;
        $skipped = 0;
        $sites = get_sites(array('fields' => 'ids', 'number' => 0));

        foreach ($sites as $site_id) {
            switch_to_blog((int)$site_id);
            foreach ($user_ids as $user_id) {
                $user_id = (int)$user_id;
                if ($user_id <= 0) {
                    continue;
                }

                $before = cpc_multisite_build_user_cloud_folder_name($user_id, $old_scope, (int)$site_id);
                $after = cpc_multisite_build_user_cloud_folder_name($user_id, $new_scope, (int)$site_id);

                if ($before === $after) {
                    $skipped++;
                    continue;
                }

                $base_dir = trailingslashit(cpc_multisite_get_user_cloud_storage_base_dir());
                $source_dir = $base_dir.$before;
                $target_dir = $base_dir.$after;

                if (file_exists($source_dir) && !file_exists($target_dir)) {
                    wp_mkdir_p(dirname($target_dir));
                    if (@rename($source_dir, $target_dir)) {
                        $migrated++;
                    } else {
                        $skipped++;
                    }
                } elseif (file_exists($source_dir) && file_exists($target_dir)) {
                    $skipped++;
                }

                $scope_key = 'cpc_activity_plus_cloud_folder_'.$new_scope;
                if (is_multisite()) {
                    $scope_key .= '_'.(int)$site_id;
                }
                update_user_meta($user_id, $scope_key, $after);
            }
            restore_current_blog();
        }

        return array('migrated' => $migrated, 'skipped' => $skipped);
    }

    function cpc_multisite_render_network_page() {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('Du hast keine Berechtigung für diese Seite.', 'cp-community'));
        }

        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpc_multisite_save'])) {
            check_admin_referer('cpc_multisite_save', 'cpc_multisite_nonce');

            $previous_user_cloud_scope = cpc_multisite_get_user_cloud_scope();
            $bloghosting_active = cpc_multisite_is_bloghosting_active();
            $bloghosting_levels = cpc_multisite_get_bloghosting_levels();

            $network_active_modules = array();
            $posted_network_active = isset($_POST['cpc_multisite_network_active']) && is_array($_POST['cpc_multisite_network_active']) ? $_POST['cpc_multisite_network_active'] : array();

            foreach (cpc_multisite_get_module_registry() as $module => $label) {
                if (!empty($posted_network_active[$module])) {
                    $network_active_modules[] = $module;
                }
            }

            update_site_option('cpc_multisite_network_active_modules', array_values(array_unique($network_active_modules)));

            if ($bloghosting_active && $bloghosting_levels) {
                $posted_level_active = isset($_POST['cpc_multisite_level_active']) && is_array($_POST['cpc_multisite_level_active']) ? $_POST['cpc_multisite_level_active'] : array();
                $level_map = array();
                foreach ($bloghosting_levels as $level_id => $level_data) {
                    $modules = isset($posted_level_active[$level_id]) && is_array($posted_level_active[$level_id]) ? array_keys($posted_level_active[$level_id]) : array();
                    $level_map[(int)$level_id] = cpc_multisite_parse_module_list($modules);
                }
                update_site_option('cpc_multisite_bloghosting_level_modules', $level_map);
            }

            $user_cloud_scope = isset($_POST['cpc_multisite_user_cloud_scope']) ? sanitize_key($_POST['cpc_multisite_user_cloud_scope']) : 'network';
            $user_cloud_scope = in_array($user_cloud_scope, array('network', 'site'), true) ? $user_cloud_scope : 'network';
            update_site_option('cpc_multisite_user_cloud_scope', $user_cloud_scope);

            $profile_media_scope = isset($_POST['cpc_multisite_profile_media_scope']) ? sanitize_key($_POST['cpc_multisite_profile_media_scope']) : 'network';
            $profile_media_scope = in_array($profile_media_scope, array('network', 'main_site'), true) ? $profile_media_scope : 'network';
            if ($user_cloud_scope === 'site') {
                // Prevent hidden local media on sub-sites when storage is site-local.
                $profile_media_scope = 'network';
            }
            update_site_option('cpc_multisite_profile_media_scope', $profile_media_scope);

            $avatar_scope = isset($_POST['cpc_multisite_avatar_scope']) ? sanitize_key($_POST['cpc_multisite_avatar_scope']) : 'inherit';
            $avatar_scope = in_array($avatar_scope, array('inherit', 'network', 'site'), true) ? $avatar_scope : 'inherit';
            update_site_option('cpc_multisite_avatar_scope', $avatar_scope);

            if (isset($_POST['cpc_multisite_user_cloud_limit_mb']) && is_numeric($_POST['cpc_multisite_user_cloud_limit_mb'])) {
                $user_cloud_limit_mb = (int)$_POST['cpc_multisite_user_cloud_limit_mb'];
                if ($user_cloud_limit_mb < 0) {
                    $user_cloud_limit_mb = 0;
                }
                if ($user_cloud_limit_mb > 10240) {
                    $user_cloud_limit_mb = 10240;
                }
                update_site_option('cpc_multisite_user_cloud_limit_mb', $user_cloud_limit_mb);
            }

            if ($previous_user_cloud_scope !== $user_cloud_scope) {
                $migration = cpc_multisite_migrate_user_cloud_scope($previous_user_cloud_scope, $user_cloud_scope);
                update_site_option('cpc_multisite_last_user_cloud_migration', array(
                    'from' => $previous_user_cloud_scope,
                    'to' => $user_cloud_scope,
                    'migrated' => isset($migration['migrated']) ? (int)$migration['migrated'] : 0,
                    'skipped' => isset($migration['skipped']) ? (int)$migration['skipped'] : 0,
                    'updated_at' => current_time('mysql'),
                ));
                if (!empty($migration['migrated'])) {
                    $message = sprintf(__('Netzwerk-Einstellungen gespeichert. %d Cloud-Bereiche wurden migriert.', 'cp-community'), (int)$migration['migrated']);
                } else {
                    $message = __('Netzwerk-Einstellungen gespeichert. Für die Cloud-Migration gab es keine passenden Verzeichnisse.', 'cp-community');
                }
            } else {
                $message = __('Netzwerk-Einstellungen gespeichert.', 'cp-community');
            }
        }

        $network_active_modules = cpc_multisite_get_network_active_modules();
        $bloghosting_active = cpc_multisite_is_bloghosting_active();
        $bloghosting_levels = cpc_multisite_get_bloghosting_levels();
        $bloghosting_level_modules_map = cpc_multisite_get_bloghosting_level_allowed_modules_map();
        $user_cloud_scope = cpc_multisite_get_user_cloud_scope();
        $user_cloud_limit_mb = cpc_multisite_get_user_cloud_limit_mb(50);
        $profile_media_scope = cpc_multisite_get_profile_media_scope();
        $avatar_scope = cpc_multisite_get_avatar_scope();
        $avatar_scope_effective = cpc_multisite_get_effective_avatar_scope();
        $profile_scope_forced = $user_cloud_scope === 'site';
        $last_migration = get_site_option('cpc_multisite_last_user_cloud_migration', array());
        $allowed_modules = cpc_multisite_get_allowed_modules_for_subsites_without_level();
        $network_managed_modules = count($allowed_modules);

        echo '<div class="wrap">';
        echo '<h1>'.esc_html__('PS Community Netzwerk', 'cp-community').'</h1>';
        echo '<p>'.esc_html__('Hier steuerst Du, welche Module vom Netzwerk vorgegeben werden und ob die User-Cloud pro Netzwerk oder pro Site begrenzt wird.', 'cp-community').'</p>';

        if ($message) {
            echo '<div class="notice notice-success is-dismissible"><p>'.esc_html($message).'</p></div>';
        }

        echo '<form method="post" action="'.esc_url(network_admin_url('admin.php?page=cpc_multisite_network')).'">';
        wp_nonce_field('cpc_multisite_save', 'cpc_multisite_nonce');

        echo '<div class="notice inline" style="margin:0 0 20px;padding:12px 16px;border-left:4px solid #2271b1;background:#fff;max-width:1000px;">';
        echo '<strong>'.esc_html__('Aktueller Status', 'cp-community').'</strong><br />';
        echo esc_html(sprintf(__('Für Subseiten erlaubte Module: %d von %d', 'cp-community'), (int)$network_managed_modules, count(cpc_multisite_get_module_registry()))).'<br />';
        if ($bloghosting_active && !empty($bloghosting_levels)) {
            echo esc_html(sprintf(__('PS Bloghosting aktiv: %d Level erkannt', 'cp-community'), count($bloghosting_levels))).'<br />';
        }
        echo esc_html(sprintf(__('User-Cloud-Scope: %s', 'cp-community'), $user_cloud_scope === 'network' ? __('netzwerkweit', 'cp-community') : __('site-lokal', 'cp-community'))).'<br />';
        echo esc_html(sprintf(__('User-Cloud-Limit pro Benutzer: %d MB', 'cp-community'), (int)$user_cloud_limit_mb));
        if (is_array($last_migration) && !empty($last_migration)) {
            echo '<br />'.esc_html(sprintf(
                __('Letzte Migration: von %1$s nach %2$s, %3$d migriert, %4$d übersprungen, am %5$s', 'cp-community'),
                isset($last_migration['from']) && $last_migration['from'] === 'network' ? __('netzwerkweit', 'cp-community') : __('site-lokal', 'cp-community'),
                isset($last_migration['to']) && $last_migration['to'] === 'network' ? __('netzwerkweit', 'cp-community') : __('site-lokal', 'cp-community'),
                isset($last_migration['migrated']) ? (int)$last_migration['migrated'] : 0,
                isset($last_migration['skipped']) ? (int)$last_migration['skipped'] : 0,
                isset($last_migration['updated_at']) ? $last_migration['updated_at'] : __('unbekannt', 'cp-community')
            ));
        }
        echo '</div>';

        echo '<h2>'.esc_html__('Module für Subsites', 'cp-community').'</h2>';
        echo '<p>'.esc_html__('Diese Liste steuert nur Subsites. Der Mainblog ist davon nicht betroffen und darf alle Module nutzen.', 'cp-community').'</p>';
        if ($bloghosting_active && !empty($bloghosting_levels)) {
            echo '<p>'.esc_html__('Bei aktivem PS Bloghosting gilt die erste Spalte für Subsites ohne Level. Für jede erkannte Levelstufe kannst Du separat definieren, welche Module erlaubt sind.', 'cp-community').'</p>';
        }
        echo '<table class="widefat striped" style="max-width:1000px">';
        echo '<thead><tr><th>'.esc_html__('Modul', 'cp-community').'</th><th>'.esc_html__('Ohne Bloghosting-Level', 'cp-community').'</th>';
        if ($bloghosting_active && !empty($bloghosting_levels)) {
            foreach ($bloghosting_levels as $level_id => $level_data) {
                echo '<th>'.esc_html(sprintf(__('Level %1$d: %2$s', 'cp-community'), (int)$level_id, $level_data['name'])).'</th>';
            }
        }
        echo '</tr></thead><tbody>';

        foreach (cpc_multisite_get_module_registry() as $module => $label) {
            $is_allowed = in_array($module, $allowed_modules, true);

            echo '<tr>';
            echo '<td><strong>'.esc_html($label).'</strong><br /><span class="description">'.esc_html($module).'</span></td>';
            echo '<td><label><input type="checkbox" name="cpc_multisite_network_active['.esc_attr($module).']" value="1"'.checked($is_allowed, true, false).' /> '.esc_html__('Erlaubt', 'cp-community').'</label></td>';
            if ($bloghosting_active && !empty($bloghosting_levels)) {
                foreach ($bloghosting_levels as $level_id => $level_data) {
                    $level_allowed = isset($bloghosting_level_modules_map[$level_id]) && in_array($module, $bloghosting_level_modules_map[$level_id], true);
                    echo '<td><label><input type="checkbox" name="cpc_multisite_level_active['.(int)$level_id.']['.esc_attr($module).']" value="1"'.checked($level_allowed, true, false).' /> '.esc_html__('Erlaubt', 'cp-community').'</label></td>';
                }
            }
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<h2 style="margin-top:28px">'.esc_html__('Site-Vorschau nach Matrix', 'cp-community').'</h2>';
        echo '<p>'.esc_html__('Zeigt je Site das erkannte PS-Bloghosting-Level und die daraus effektiven Modulrechte.', 'cp-community').'</p>';

        $preview_limit = max(1, (int)apply_filters('cpc_multisite_preview_sites_limit', 30));
        $preview_sites = get_sites(array(
            'number' => $preview_limit,
            'orderby' => 'id',
            'order' => 'ASC',
            'spam' => 0,
            'deleted' => 0,
            'archived' => 0,
        ));
        $preview_total_sites = (int)get_sites(array(
            'count' => true,
            'spam' => 0,
            'deleted' => 0,
            'archived' => 0,
        ));

        echo '<table class="widefat striped" style="max-width:1200px">';
        echo '<thead><tr>';
        echo '<th>'.esc_html__('Site', 'cp-community').'</th>';
        echo '<th>'.esc_html__('Blog-ID', 'cp-community').'</th>';
        echo '<th>'.esc_html__('Erkanntes Level', 'cp-community').'</th>';
        echo '<th>'.esc_html__('Effektiv erlaubte Module', 'cp-community').'</th>';
        echo '</tr></thead><tbody>';

        if (!empty($preview_sites)) {
            $registry = cpc_multisite_get_module_registry();
            foreach ($preview_sites as $site) {
                $site_blog_id = (int)$site->blog_id;
                $site_name = get_blog_option($site_blog_id, 'blogname');
                if (!is_string($site_name) || $site_name === '') {
                    $site_name = sprintf(__('Site %d', 'cp-community'), $site_blog_id);
                }
                $site_url = get_home_url($site_blog_id, '/');

                if (cpc_multisite_is_main_blog($site_blog_id)) {
                    $level_label = __('Mainblog (immer alles erlaubt)', 'cp-community');
                    $effective_modules_text = __('Alle Module', 'cp-community');
                } else {
                    $site_level = cpc_multisite_get_current_bloghosting_level($site_blog_id);
                    $level_label = cpc_multisite_get_bloghosting_level_label($site_level, $bloghosting_levels);
                    $effective_modules = cpc_multisite_get_allowed_modules_for_subsites($site_blog_id, $site_level);

                    $effective_labels = array();
                    foreach ($registry as $module => $label) {
                        if (in_array($module, $effective_modules, true)) {
                            $effective_labels[] = $label;
                        }
                    }

                    $effective_modules_text = !empty($effective_labels) ? implode(', ', $effective_labels) : __('Keine', 'cp-community');
                }

                echo '<tr>';
                echo '<td><strong>'.esc_html($site_name).'</strong><br /><span class="description">'.esc_html($site_url).'</span></td>';
                echo '<td>'.(int)$site_blog_id.'</td>';
                echo '<td>'.esc_html($level_label).'</td>';
                echo '<td>'.esc_html($effective_modules_text).'</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4">'.esc_html__('Keine Sites gefunden.', 'cp-community').'</td></tr>';
        }

        echo '</tbody></table>';
        if ($preview_total_sites > $preview_limit) {
            echo '<p class="description">'.esc_html(sprintf(__('Es werden %1$d von %2$d Sites angezeigt. Das Limit kann über den Filter cpc_multisite_preview_sites_limit angepasst werden.', 'cp-community'), $preview_limit, $preview_total_sites)).'</p>';
        }

        echo '<h2 style="margin-top:28px">'.esc_html__('User-Cloud Speicher', 'cp-community').'</h2>';
        echo '<table class="form-table" style="max-width:1000px">';
        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="cpc_multisite_user_cloud_scope">'.esc_html__('Speicher-Scope', 'cp-community').'</label></th>';
        echo '<td><select name="cpc_multisite_user_cloud_scope" id="cpc_multisite_user_cloud_scope">';
        echo '<option value="network"'.selected($user_cloud_scope, 'network', false).'>'.esc_html__('Netzwerkweit pro Benutzer', 'cp-community').'</option>';
        echo '<option value="site"'.selected($user_cloud_scope, 'site', false).'>'.esc_html__('Pro Site und Benutzer', 'cp-community').'</option>';
        echo '</select><br />';
        echo '<span class="description">'.esc_html__('Gilt für Activity+ und Media, solange deren Speicher über die gemeinsame User-Cloud läuft.', 'cp-community').'</span></td>';
        echo '</tr>';
        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="cpc_multisite_user_cloud_limit_mb">'.esc_html__('Limit pro Benutzer (MB)', 'cp-community').'</label></th>';
        echo '<td><input type="number" min="0" max="10240" step="1" style="width:90px" name="cpc_multisite_user_cloud_limit_mb" id="cpc_multisite_user_cloud_limit_mb" value="'.esc_attr($user_cloud_limit_mb).'" />';
        echo '<span class="description" style="display:block">'.esc_html__('0 bedeutet unbegrenzt. Das ist das Netzwerk-Default für die User-Cloud.', 'cp-community').'</span></td>';
        echo '</tr>';
        echo '</table>';

        echo '<h2 style="margin-top:28px">'.esc_html__('Profil-Medien', 'cp-community').'</h2>';
        echo '<table class="form-table" style="max-width:1000px">';
        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="cpc_multisite_profile_media_scope">'.esc_html__('Anzeige im Profil', 'cp-community').'</label></th>';
        echo '<td><select name="cpc_multisite_profile_media_scope" id="cpc_multisite_profile_media_scope">';
        echo '<option value="network"'.selected($profile_media_scope, 'network', false).'>'.esc_html__('Netzwerkweit in allen Profilen', 'cp-community').'</option>';
        echo '<option value="main_site"'.selected($profile_media_scope, 'main_site', false).($profile_scope_forced ? ' disabled="disabled"' : '').'>'.esc_html__('Nur im Mainblog-Profil', 'cp-community').'</option>';
        echo '</select><br />';
        echo '<span class="description">'.esc_html__('Steuert nur die Sichtbarkeit des Medien-Tabs im Profil. Die Dateien selbst bleiben zentral verfügbar.', 'cp-community').'</span></td>';
        if ($profile_scope_forced) {
            echo '<span class="description" style="display:block">'.esc_html__('Hinweis: Bei Site-lokalem User-Cloud-Scope bleibt der Profil-Medien-Tab auf Subsites aktiv, damit lokale Uploads verwaltet und gelöscht werden können.', 'cp-community').'</span>';
        }
        echo '</tr>';
        echo '</table>';

        echo '<h2 style="margin-top:28px">'.esc_html__('Avatar-Synchronisierung', 'cp-community').'</h2>';
        echo '<table class="form-table" style="max-width:1000px">';
        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="cpc_multisite_avatar_scope">'.esc_html__('Avatar-Scope', 'cp-community').'</label></th>';
        echo '<td><select name="cpc_multisite_avatar_scope" id="cpc_multisite_avatar_scope">';
        echo '<option value="inherit"'.selected($avatar_scope, 'inherit', false).'>'.esc_html__('User-Cloud folgen (empfohlen)', 'cp-community').'</option>';
        echo '<option value="network"'.selected($avatar_scope, 'network', false).'>'.esc_html__('Immer netzwerkweit', 'cp-community').'</option>';
        echo '<option value="site"'.selected($avatar_scope, 'site', false).'>'.esc_html__('Immer pro Site', 'cp-community').'</option>';
        echo '</select><br />';
        echo '<span class="description">'.esc_html__('Gilt nur für Avatar-Dateipfade. Effektiv aktiv: ', 'cp-community').'<strong>'.esc_html($avatar_scope_effective === 'network' ? __('netzwerkweit', 'cp-community') : __('site-lokal', 'cp-community')).'</strong></span></td>';
        echo '</tr>';
        echo '</table>';

        echo '<p><input type="submit" class="button button-primary" name="cpc_multisite_save" value="'.esc_attr__('Einstellungen speichern', 'cp-community').'" /></p>';
        echo '</form>';
        echo '</div>';
    }

    add_action('network_admin_menu', function() {
        if (!is_multisite() || !current_user_can('manage_network_options')) {
            return;
        }

        add_menu_page(
            __('PS Community Netzwerk', 'cp-community'),
            __('PS Community', 'cp-community'),
            'manage_network_options',
            'cpc_multisite_network',
            'cpc_multisite_render_network_page',
            'dashicons-networking',
            58
        );
    });
}