<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CPC_MarketPress_Profile_Integration' ) ) {
    class CPC_MarketPress_Profile_Integration {

        const OPTION_KEY = 'mpcpc_settings';

        public function __construct() {
            add_action( 'cpc_integrations_settings', array( $this, 'render_settings_page' ) );
            add_action( 'plugins_loaded', array( $this, 'remove_legacy_bridge_hooks' ), 100 );
            add_action( 'init', array( $this, 'register_runtime_hooks' ), 20 );
        }

        public function remove_legacy_bridge_hooks() {
            global $wp_filter;

            $legacy_hooks = array(
                'cpc_integrations_settings',
                'add_meta_boxes',
                'save_post',
                'cpc_profile_tabs',
                'cpc_profile_tab_content',
                'mp_order/new_order',
                'mp_order_order_paid',
                'mp_order_order_shipped',
                'mp_order_order_closed',
                'mp_order_trashed',
            );

            foreach ( $legacy_hooks as $hook_name ) {
                if ( empty( $wp_filter[ $hook_name ] ) || empty( $wp_filter[ $hook_name ]->callbacks ) ) {
                    continue;
                }

                foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
                    foreach ( $callbacks as $callback ) {
                        $function = isset( $callback['function'] ) ? $callback['function'] : null;
                        if ( is_array( $function ) && is_object( $function[0] ) && is_a( $function[0], 'PS_MarketPress_Community_Bridge' ) ) {
                            remove_filter( $hook_name, $function, $priority );
                        }
                    }
                }
            }
        }

        private function defaults() {
            return array(
                'enabled' => 1,
                'profile_tab_enabled' => 1,
                'profile_tab_label' => __( 'Meine Bestellungen', 'cp-community' ),
                'profile_tab_priority' => 45,
            );
        }

        private function get_settings() {
            $saved = get_option( self::OPTION_KEY, array() );
            return array_merge( $this->defaults(), is_array( $saved ) ? $saved : array() );
        }

        private function get_marketpress_status() {
            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $plugin_file = 'marketpress/marketpress.php';
            $plugins = get_plugins();
            $installed = isset( $plugins[ $plugin_file ] );

            return array(
                'installed' => $installed,
                'active' => $installed && is_plugin_active( $plugin_file ),
                'file' => $plugin_file,
                'name' => $installed ? $plugins[ $plugin_file ]['Name'] : '',
                'version' => $installed ? $plugins[ $plugin_file ]['Version'] : '',
            );
        }

        public function register_runtime_hooks() {
            $settings = $this->get_settings();
            if ( empty( $settings['enabled'] ) || ! class_exists( 'Marketpress' ) || ! function_exists( 'mp_order_status' ) || ! function_exists( 'cpc_get_profile_tabs' ) ) {
                return;
            }

            add_filter( 'cpc_profile_tabs', array( $this, 'add_profile_tab' ), 20, 3 );
            add_filter( 'cpc_profile_tab_content', array( $this, 'render_profile_tab_content' ), 20, 4 );
        }

        public function add_profile_tab( $tabs, $user_id, $viewer_id ) {
            $settings = $this->get_settings();
            if ( empty( $settings['profile_tab_enabled'] ) || (int) $user_id !== (int) $viewer_id ) {
                return $tabs;
            }

            $tabs['marketpress'] = array(
                'label' => sanitize_text_field( $settings['profile_tab_label'] ),
                'icon' => 'cart',
                'priority' => (int) $settings['profile_tab_priority'],
            );

            return $tabs;
        }

        public function render_profile_tab_content( $html, $active_tab, $user_id, $shortcode_atts ) {
            if ( 'marketpress' !== $active_tab ) {
                return $html;
            }

            if ( ! is_user_logged_in() || (int) $user_id !== get_current_user_id() ) {
                return '<div class="cpc-error">' . esc_html__( 'Dieser Bereich ist nur im eigenen Profil verfuegbar.', 'cp-community' ) . '</div>';
            }

            if ( class_exists( 'MP_Short_Codes' ) ) {
                MP_Short_Codes::get_instance()->shortcodes_frontend_styles_scripts();
            }

            return '<div class="mpcpc-profile-orders">' . mp_order_status( array( 'echo' => false ) ) . '</div>';
        }

        private function sanitize_settings( $input ) {
            $defaults = $this->defaults();
            $input = is_array( $input ) ? $input : array();

            return array(
                'enabled' => empty( $input['enabled'] ) ? 0 : 1,
                'profile_tab_enabled' => empty( $input['profile_tab_enabled'] ) ? 0 : 1,
                'profile_tab_label' => sanitize_text_field( isset( $input['profile_tab_label'] ) ? $input['profile_tab_label'] : $defaults['profile_tab_label'] ),
                'profile_tab_priority' => max( 1, (int) ( isset( $input['profile_tab_priority'] ) ? $input['profile_tab_priority'] : $defaults['profile_tab_priority'] ) ),
            );
        }

        public function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $status = $this->get_marketpress_status();
            if ( $status['active'] && isset( $_POST['mpcpc_integration_save'] ) ) {
                check_admin_referer( 'mpcpc_integration_settings' );
                $input = isset( $_POST[ self::OPTION_KEY ] ) ? wp_unslash( $_POST[ self::OPTION_KEY ] ) : array();
                update_option( self::OPTION_KEY, $this->sanitize_settings( $input ) );
                echo '<div class="notice notice-success inline"><p>' . esc_html__( 'MarketPress-Profilintegration gespeichert.', 'cp-community' ) . '</p></div>';
            }

            $settings = $this->get_settings();
            ?>
            <div class="cpc-integration-box" style="border:1px solid #ddd;padding:20px;margin-top:20px;background:#f9f9f9;border-radius:5px;">
                <h2><?php echo esc_html__( 'PS MarketPress Integration', 'cp-community' ); ?></h2>
                <p><?php echo esc_html__( 'Zeigt die MarketPress-Bestellungen des aktuellen Blogs im eigenen PS-Community-Profil an.', 'cp-community' ); ?></p>

                <?php if ( $status['active'] ) : ?>
                    <div class="notice <?php echo ! empty( $settings['enabled'] ) ? 'notice-success' : 'notice-warning'; ?> inline" style="margin:0 0 20px;">
                        <p><strong><?php echo esc_html__( 'MarketPress:', 'cp-community' ); ?></strong> <?php echo esc_html__( 'Plugin aktiv', 'cp-community' ); ?></p>
                        <p><strong><?php echo esc_html__( 'Profilintegration:', 'cp-community' ); ?></strong> <?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Aktiviert', 'cp-community' ) : esc_html__( 'Deaktiviert', 'cp-community' ); ?></p>
                        <p><strong><?php echo esc_html__( 'Version:', 'cp-community' ); ?></strong> <?php echo esc_html( trim( $status['name'] . ' ' . $status['version'] ) ); ?></p>
                    </div>

                    <details class="mpcpc-integration-settings">
                        <summary style="cursor:pointer;font-weight:600;padding:8px 0;"><?php echo esc_html__( 'Profilintegration konfigurieren', 'cp-community' ); ?></summary>
                        <form method="post" action="" style="padding-top:8px;">
                            <?php wp_nonce_field( 'mpcpc_integration_settings' ); ?>
                            <table class="form-table" role="presentation">
                                <tr><th scope="row"><?php echo esc_html__( 'Integration', 'cp-community' ); ?></th><td>
                                    <input type="hidden" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="0">
                                    <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php echo esc_html__( 'Lokale MarketPress-Kundenzone bereitstellen', 'cp-community' ); ?></label>
                                </td></tr>
                                <tr><th scope="row"><?php echo esc_html__( 'Profil-Tab', 'cp-community' ); ?></th><td>
                                    <input type="hidden" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_tab_enabled]" value="0">
                                    <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_tab_enabled]" value="1" <?php checked( ! empty( $settings['profile_tab_enabled'] ) ); ?>> <?php echo esc_html__( 'Im eigenen Profil anzeigen', 'cp-community' ); ?></label>
                                </td></tr>
                                <tr><th scope="row"><label for="mpcpc-tab-label"><?php echo esc_html__( 'Name des Profil-Tabs', 'cp-community' ); ?></label></th><td><input id="mpcpc-tab-label" type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_tab_label]" value="<?php echo esc_attr( $settings['profile_tab_label'] ); ?>" class="regular-text"></td></tr>
                                <tr><th scope="row"><label for="mpcpc-tab-priority"><?php echo esc_html__( 'Tab-Prioritaet', 'cp-community' ); ?></label></th><td><input id="mpcpc-tab-priority" type="number" min="1" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_tab_priority]" value="<?php echo esc_attr( (int) $settings['profile_tab_priority'] ); ?>" class="small-text"></td></tr>
                            </table>
                            <?php submit_button( __( 'Einstellungen speichern', 'cp-community' ), 'primary', 'mpcpc_integration_save' ); ?>
                        </form>
                    </details>
                <?php elseif ( $status['installed'] ) : ?>
                    <div class="notice notice-warning inline"><p><?php echo esc_html__( 'PS MarketPress ist installiert, aber deaktiviert.', 'cp-community' ); ?></p></div>
                    <p><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $status['file'] ), 'activate-plugin_' . $status['file'] ) ); ?>" class="button button-primary"><?php echo esc_html__( 'PS MarketPress aktivieren', 'cp-community' ); ?></a></p>
                <?php else : ?>
                    <div class="notice notice-info inline"><p><?php echo esc_html__( 'PS MarketPress ist nicht installiert.', 'cp-community' ); ?></p></div>
                <?php endif; ?>
            </div>
            <?php
        }
    }
}

new CPC_MarketPress_Profile_Integration();