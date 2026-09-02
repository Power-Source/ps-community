<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'PS_MarketPress_Community_Bridge' ) ) {

    class PS_MarketPress_Community_Bridge {

        const OPTION_KEY = 'mpcpc_settings';

        public function __construct() {
            add_action( 'cpc_integrations_settings', array( $this, 'render_settings_page' ) );
            add_action( 'add_meta_boxes', array( $this, 'register_product_meta_box' ) );
            add_action( 'save_post', array( $this, 'save_product_group_meta' ), 10, 2 );
            add_action( 'plugins_loaded', array( $this, 'register_runtime_hooks' ), 30 );
        }

        public static function defaults() {
            return array(
                'enabled' => 1,
                'profile_tab_enabled' => 1,
                'profile_tab_label' => 'Meine Bestellungen',
                'profile_tab_priority' => 45,

                'alerts_enabled' => 1,
                'alert_to_buyer' => 1,
                'alert_to_admin' => 1,
                'alert_new_order' => 1,
                'alert_paid' => 1,
                'alert_shipped' => 1,
                'alert_closed' => 0,
                'alert_trashed' => 0,

                'activity_enabled' => 1,
                'activity_to_profile' => 1,
                'activity_to_group' => 1,
                'activity_new_order' => 1,
                'activity_paid' => 1,
                'activity_shipped' => 1,
                'activity_closed' => 0,
                'activity_trashed' => 0,

                'group_context_enabled' => 1,
                'group_meta_key' => 'cpc_group_id',
                'group_fallback' => 'profile',

                'actor_user_id' => 0,
                'privacy_hide_amounts' => 1,
                'debug_log' => 0,
            );
        }

        public function get_settings() {
            $saved = get_option( self::OPTION_KEY, array() );
            if ( ! is_array( $saved ) ) {
                $saved = array();
            }
            return array_merge( self::defaults(), $saved );
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
                'name' => $installed ? $plugins[ $plugin_file ]['Name'] : null,
                'version' => $installed ? $plugins[ $plugin_file ]['Version'] : null,
            );
        }

        public function register_runtime_hooks() {
            if ( ! $this->is_integration_ready() ) {
                return;
            }

            add_filter( 'cpc_profile_tabs', array( $this, 'add_profile_tab' ), 20, 3 );
            add_filter( 'cpc_profile_tab_content', array( $this, 'render_profile_tab_content' ), 20, 4 );

            add_action( 'mp_order/new_order', array( $this, 'handle_new_order' ), 20, 1 );
            add_action( 'mp_order_order_paid', array( $this, 'handle_order_paid' ), 20, 1 );
            add_action( 'mp_order_order_shipped', array( $this, 'handle_order_shipped' ), 20, 1 );
            add_action( 'mp_order_order_closed', array( $this, 'handle_order_closed' ), 20, 1 );
            add_action( 'mp_order_trashed', array( $this, 'handle_order_trashed' ), 20, 1 );
        }

        private function is_integration_ready() {
            $s = $this->get_settings();
            if ( empty( $s['enabled'] ) ) {
                return false;
            }

            if ( ! class_exists( 'Marketpress' ) || ! class_exists( 'MP_Order' ) ) {
                return false;
            }

            if ( ! function_exists( 'cpc_get_profile_tabs' ) ) {
                return false;
            }

            return true;
        }

        public function add_profile_tab( $tabs, $user_id, $viewer_id ) {
            $s = $this->get_settings();
            if ( empty( $s['profile_tab_enabled'] ) ) {
                return $tabs;
            }

            if ( ! $this->can_view_orders_tab( (int) $user_id, (int) $viewer_id ) ) {
                return $tabs;
            }

            $tabs['marketpress'] = array(
                'label' => sanitize_text_field( $s['profile_tab_label'] ),
                'icon' => 'cart',
                'priority' => (int) $s['profile_tab_priority'],
            );

            return $tabs;
        }

        public function render_profile_tab_content( $html, $active_tab, $user_id, $shortcode_atts ) {
            if ( 'marketpress' !== $active_tab ) {
                return $html;
            }

            if ( ! is_user_logged_in() ) {
                return '<div class="cpc-error">' . esc_html__( 'Bitte melde Dich an, um Deine Bestellungen zu sehen.', 'cp-community' ) . '</div>';
            }

            if ( ! $this->can_view_orders_tab( (int) $user_id, get_current_user_id() ) ) {
                return '<div class="cpc-error">' . esc_html__( 'Du darfst diesen Bereich nicht ansehen.', 'cp-community' ) . '</div>';
            }

            if ( class_exists( 'MP_Short_Codes' ) ) {
                MP_Short_Codes::get_instance()->shortcodes_frontend_styles_scripts();
            }

            if ( ! function_exists( 'mp_order_status' ) ) {
                return '<div class="cpc-error">' . esc_html__( 'MarketPress ist nicht verfuegbar.', 'cp-community' ) . '</div>';
            }

            $out  = '<div class="mpcpc-profile-orders">';
            $out .= '<h3>' . esc_html__( 'Meine Bestellungen', 'cp-community' ) . '</h3>';
            $out .= mp_order_status( array( 'echo' => false ) );
            $out .= '</div>';

            return $out;
        }

        private function can_view_orders_tab( $profile_user_id, $viewer_id ) {
            if ( $viewer_id <= 0 ) {
                $viewer_id = get_current_user_id();
            }
            if ( $profile_user_id === $viewer_id ) {
                return true;
            }
            return current_user_can( 'manage_options' );
        }

        public function handle_new_order( $order ) {
            $this->process_order_event( 'new_order', $order );
        }

        public function handle_order_paid( $order ) {
            $this->process_order_event( 'paid', $order );
        }

        public function handle_order_shipped( $order ) {
            $this->process_order_event( 'shipped', $order );
        }

        public function handle_order_closed( $order ) {
            $this->process_order_event( 'closed', $order );
        }

        public function handle_order_trashed( $order ) {
            $this->process_order_event( 'trashed', $order );
        }

        private function process_order_event( $event, $order ) {
            if ( ! $order || ! is_object( $order ) || ! isset( $order->ID ) ) {
                return;
            }

            $settings = $this->get_settings();
            $order_id = (int) $order->ID;

            if ( $this->event_already_processed( $order_id, $event ) ) {
                $this->debug( 'Event already processed: ' . $event . ' #' . $order_id );
                return;
            }

            $buyer_id = (int) $order->post_author;
            $context  = $this->build_context( $event, $order );

            if ( ! empty( $settings['alerts_enabled'] ) && ! empty( $settings[ 'alert_' . $event ] ) ) {
                $this->dispatch_alerts( $context, $buyer_id );
            }

            if ( ! empty( $settings['activity_enabled'] ) && ! empty( $settings[ 'activity_' . $event ] ) ) {
                $this->dispatch_activity( $context, $buyer_id );
            }

            update_post_meta( $order_id, '_mpcpc_event_' . sanitize_key( $event ), current_time( 'mysql', true ) );
        }

        private function event_already_processed( $order_id, $event ) {
            return (bool) get_post_meta( $order_id, '_mpcpc_event_' . sanitize_key( $event ), true );
        }

        private function build_context( $event, $order ) {
            $settings = $this->get_settings();
            $order_id = (int) $order->ID;

            $url = '';
            if ( function_exists( 'mp_orderstatus_link' ) ) {
                $url = mp_orderstatus_link( false, false, '', $order_id );
            }
            if ( empty( $url ) ) {
                $url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
            }

            $label = $this->event_label( $event );
            $msg   = sprintf( __( 'Bestellung #%d: %s', 'cp-community' ), $order_id, $label );

            if ( empty( $settings['privacy_hide_amounts'] ) ) {
                $total = method_exists( $order, 'get_meta' ) ? $order->get_meta( 'mp_order_total', '' ) : '';
                if ( '' !== $total && null !== $total ) {
                    $msg .= ' (' . sprintf( __( 'Gesamt: %s', 'cp-community' ), $total ) . ')';
                }
            }

            return array(
                'event' => $event,
                'order_id' => $order_id,
                'url' => $url,
                'message' => $msg,
                'subject' => sprintf( __( 'Shop-Update zu Bestellung #%d', 'cp-community' ), $order_id ),
                'actor_id' => $this->resolve_actor_id( $order ),
                'group_id' => $this->resolve_group_id_from_order( $order ),
                'order' => $order,
            );
        }

        private function resolve_actor_id( $order ) {
            $settings = $this->get_settings();
            $configured = (int) $settings['actor_user_id'];
            if ( $configured > 0 ) {
                return $configured;
            }

            $buyer_id = (int) $order->post_author;
            if ( $buyer_id > 0 ) {
                return $buyer_id;
            }

            $current = get_current_user_id();
            if ( $current > 0 ) {
                return $current;
            }

            return 1;
        }

        private function resolve_group_id_from_order( $order ) {
            $settings = $this->get_settings();
            if ( empty( $settings['group_context_enabled'] ) ) {
                return 0;
            }

            $meta_key = sanitize_key( $settings['group_meta_key'] );
            if ( empty( $meta_key ) ) {
                return 0;
            }

            if ( ! method_exists( $order, 'get_cart' ) ) {
                return 0;
            }

            $cart = $order->get_cart();
            if ( ! $cart || ! method_exists( $cart, 'get_items_as_objects' ) ) {
                return 0;
            }

            $items = $cart->get_items_as_objects();
            if ( ! is_array( $items ) ) {
                return 0;
            }

            foreach ( $items as $item ) {
                if ( ! is_object( $item ) || empty( $item->ID ) ) {
                    continue;
                }
                $candidate = (int) get_post_meta( (int) $item->ID, $meta_key, true );
                if ( $candidate > 0 && get_post( $candidate ) ) {
                    return $candidate;
                }
            }

            return 0;
        }

        private function dispatch_alerts( $context, $buyer_id ) {
            $settings = $this->get_settings();
            $targets  = array();

            if ( ! empty( $settings['alert_to_buyer'] ) && $buyer_id > 0 ) {
                $targets[] = $buyer_id;
            }

            if ( ! empty( $settings['alert_to_admin'] ) ) {
                $admins = get_users( array(
                    'fields' => array( 'ID' ),
                    'role__in' => array( 'administrator' ),
                ) );
                foreach ( $admins as $admin ) {
                    $targets[] = (int) $admin->ID;
                }
            }

            $targets = array_values( array_unique( array_filter( $targets ) ) );
            if ( empty( $targets ) ) {
                return;
            }

            foreach ( $targets as $recipient_id ) {
                $this->insert_alert( $recipient_id, $context );
            }
        }

        private function insert_alert( $recipient_id, $context ) {
            if ( ! post_type_exists( 'cpc_alerts' ) ) {
                return;
            }

            $recipient = get_user_by( 'id', (int) $recipient_id );
            if ( ! $recipient ) {
                return;
            }

            $post = array(
                'post_title' => $context['subject'],
                'post_excerpt' => $context['message'],
                'post_content' => $context['message'],
                'post_status' => 'publish',
                'post_type' => 'cpc_alerts',
                'post_author' => (int) $context['actor_id'],
                'ping_status' => 'closed',
                'comment_status' => 'closed',
            );

            $alert_id = wp_insert_post( $post, true );
            if ( is_wp_error( $alert_id ) || ! $alert_id ) {
                return;
            }

            update_post_meta( $alert_id, 'cpc_alert_recipient', $recipient->user_login );
            update_post_meta( $alert_id, 'cpc_alert_target', 'marketpress' );
            update_post_meta( $alert_id, 'cpc_alert_parameters', 'order=' . (int) $context['order_id'] . ';event=' . sanitize_key( $context['event'] ) );
            update_post_meta( $alert_id, 'cpc_alert_url', esc_url_raw( $context['url'] ) );
            update_post_meta( $alert_id, 'cpc_alert_msg', $context['message'] );

            do_action( 'cpc_alert_add_hook', (int) $recipient_id, (int) $alert_id, $context['url'], $context['message'] );
        }

        private function dispatch_activity( $context, $buyer_id ) {
            $settings = $this->get_settings();
            $message  = $context['message'];
            $profile_written = false;

            if ( ! post_type_exists( 'cpc_activity' ) ) {
                return;
            }

            if ( ! empty( $settings['activity_to_profile'] ) && $buyer_id > 0 ) {
                $this->insert_profile_activity( $buyer_id, $context['actor_id'], $message, $context['url'] );
                $profile_written = true;
            }

            if ( empty( $settings['activity_to_group'] ) ) {
                return;
            }

            $group_id = (int) $context['group_id'];
            if ( $group_id > 0 ) {
                if ( function_exists( 'cpc_is_group_member' ) && $buyer_id > 0 && ! cpc_is_group_member( $buyer_id, $group_id ) ) {
                    $this->fallback_activity_without_group( $settings, $buyer_id, $context, $profile_written );
                    return;
                }

                $this->insert_group_activity( $group_id, $context['actor_id'], $message, $context['url'] );
                return;
            }

            $this->fallback_activity_without_group( $settings, $buyer_id, $context, $profile_written );
        }

        private function fallback_activity_without_group( $settings, $buyer_id, $context, $profile_written = false ) {
            $fallback = isset( $settings['group_fallback'] ) ? $settings['group_fallback'] : 'profile';
            if ( 'none' === $fallback ) {
                return;
            }

            if ( $buyer_id > 0 && ! $profile_written ) {
                $this->insert_profile_activity( $buyer_id, $context['actor_id'], $context['message'], $context['url'] );
            }
        }

        private function insert_profile_activity( $target_user_id, $actor_user_id, $message, $url ) {
            $activity_text = $message;
            if ( ! empty( $url ) ) {
                $activity_text .= ' [a] href="' . esc_url( $url ) . '"[a2]' . esc_html__( 'Details ansehen', 'cp-community' ) . '[/a]';
            }

            $post = array(
                'post_title' => $activity_text,
                'post_content' => $activity_text,
                'post_status' => 'publish',
                'post_type' => 'cpc_activity',
                'post_author' => (int) $actor_user_id,
                'ping_status' => 'closed',
                'comment_status' => 'open',
            );

            $activity_id = wp_insert_post( $post, true );
            if ( is_wp_error( $activity_id ) || ! $activity_id ) {
                return;
            }

            update_post_meta( $activity_id, 'cpc_target', (int) $target_user_id );
            update_post_meta( $activity_id, 'cpc_activity_visibility', 'public' );
        }

        private function insert_group_activity( $group_id, $actor_user_id, $message, $url ) {
            $activity_text = $message;
            if ( ! empty( $url ) ) {
                $activity_text .= ' [a] href="' . esc_url( $url ) . '"[a2]' . esc_html__( 'Bestellung', 'cp-community' ) . '[/a]';
            }

            $post = array(
                'post_title' => $activity_text,
                'post_content' => $activity_text,
                'post_status' => 'publish',
                'post_type' => 'cpc_activity',
                'post_author' => (int) $actor_user_id,
                'ping_status' => 'closed',
                'comment_status' => 'open',
            );

            $activity_id = wp_insert_post( $post, true );
            if ( is_wp_error( $activity_id ) || ! $activity_id ) {
                return;
            }

            update_post_meta( $activity_id, 'cpc_target', (int) $actor_user_id );
            update_post_meta( $activity_id, 'cpc_activity_group_id', (int) $group_id );
            update_post_meta( $activity_id, 'cpc_activity_type', 'group_activity' );
            update_post_meta( $activity_id, 'cpc_activity_visibility', 'public' );

            do_action( 'cpc_group_activity_post_add_hook', array(), array(), (int) $activity_id, (int) $group_id );
        }

        private function event_label( $event ) {
            switch ( $event ) {
                case 'new_order':
                    return __( 'neu eingegangen', 'cp-community' );
                case 'paid':
                    return __( 'bezahlt', 'cp-community' );
                case 'shipped':
                    return __( 'versendet', 'cp-community' );
                case 'closed':
                    return __( 'abgeschlossen', 'cp-community' );
                case 'trashed':
                    return __( 'storniert/entfernt', 'cp-community' );
                default:
                    return sanitize_text_field( $event );
            }
        }

        public function register_product_meta_box() {
            $supported = array( 'mp_product', 'product' );
            foreach ( $supported as $post_type ) {
                if ( post_type_exists( $post_type ) ) {
                    add_meta_box(
                        'mpcpc_group_context',
                        __( 'PS Community Gruppenkontext', 'cp-community' ),
                        array( $this, 'render_product_group_meta_box' ),
                        $post_type,
                        'side',
                        'default'
                    );
                }
            }
        }

        public function render_product_group_meta_box( $post ) {
            $settings = $this->get_settings();
            $meta_key = sanitize_key( $settings['group_meta_key'] );
            if ( empty( $meta_key ) ) {
                $meta_key = 'cpc_group_id';
            }

            $current_group_id = (int) get_post_meta( $post->ID, $meta_key, true );

            wp_nonce_field( 'mpcpc_group_meta_save', 'mpcpc_group_meta_nonce' );

            echo '<p>' . esc_html__( 'Ordnet dieses Produkt einer PS Community Gruppe zu. Diese Zuordnung wird fuer Gruppen-Activity-Kontext verwendet.', 'cp-community' ) . '</p>';

            $groups = array();
            if ( post_type_exists( 'cpc_group' ) ) {
                $groups = get_posts( array(
                    'post_type' => 'cpc_group',
                    'post_status' => 'publish',
                    'posts_per_page' => 200,
                    'orderby' => 'title',
                    'order' => 'ASC',
                ) );
            }

            echo '<p><label for="mpcpc_group_id"><strong>' . esc_html__( 'Gruppe', 'cp-community' ) . '</strong></label></p>';
            echo '<select name="mpcpc_group_id" id="mpcpc_group_id" style="width:100%">';
            echo '<option value="0">' . esc_html__( 'Keine Gruppenzuordnung', 'cp-community' ) . '</option>';

            if ( ! empty( $groups ) ) {
                foreach ( $groups as $group ) {
                    echo '<option value="' . esc_attr( (int) $group->ID ) . '" ' . selected( $current_group_id, (int) $group->ID, false ) . '>' . esc_html( $group->post_title ) . ' (#' . (int) $group->ID . ')' . '</option>';
                }
            }

            echo '</select>';

            echo '<p style="margin-top:10px">';
            echo '<label for="mpcpc_group_id_manual"><strong>' . esc_html__( 'Oder manuelle Gruppen-ID', 'cp-community' ) . '</strong></label>';
            echo '<input type="number" min="0" name="mpcpc_group_id_manual" id="mpcpc_group_id_manual" value="' . esc_attr( $current_group_id ) . '" style="width:100%" />';
            echo '</p>';

            echo '<p class="description">' . esc_html__( 'Gespeichert wird im Produkt-Meta-Key:', 'cp-community' ) . ' <code>' . esc_html( $meta_key ) . '</code></p>';
        }

        public function save_product_group_meta( $post_id, $post ) {
            if ( ! $post || ! in_array( $post->post_type, array( 'mp_product', 'product' ), true ) ) {
                return;
            }

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            if ( ! isset( $_POST['mpcpc_group_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcpc_group_meta_nonce'] ) ), 'mpcpc_group_meta_save' ) ) {
                return;
            }

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            $settings = $this->get_settings();
            $meta_key = sanitize_key( $settings['group_meta_key'] );
            if ( empty( $meta_key ) ) {
                $meta_key = 'cpc_group_id';
            }

            $selected = isset( $_POST['mpcpc_group_id'] ) ? (int) $_POST['mpcpc_group_id'] : 0;
            $manual   = isset( $_POST['mpcpc_group_id_manual'] ) ? (int) $_POST['mpcpc_group_id_manual'] : 0;
            $group_id = $manual > 0 ? $manual : $selected;

            if ( $group_id > 0 ) {
                update_post_meta( $post_id, $meta_key, $group_id );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }

        public function sanitize_settings( $input ) {
            $defaults = self::defaults();
            $output   = $defaults;
            $input    = is_array( $input ) ? $input : array();

            $checkbox_keys = array(
                'enabled',
                'profile_tab_enabled',
                'alerts_enabled',
                'alert_to_buyer',
                'alert_to_admin',
                'alert_new_order',
                'alert_paid',
                'alert_shipped',
                'alert_closed',
                'alert_trashed',
                'activity_enabled',
                'activity_to_profile',
                'activity_to_group',
                'activity_new_order',
                'activity_paid',
                'activity_shipped',
                'activity_closed',
                'activity_trashed',
                'group_context_enabled',
                'privacy_hide_amounts',
                'debug_log',
            );

            foreach ( $checkbox_keys as $key ) {
                $output[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
            }

            $output['profile_tab_label'] = sanitize_text_field( isset( $input['profile_tab_label'] ) ? $input['profile_tab_label'] : $defaults['profile_tab_label'] );
            $output['profile_tab_priority'] = max( 1, (int) ( isset( $input['profile_tab_priority'] ) ? $input['profile_tab_priority'] : $defaults['profile_tab_priority'] ) );
            $output['group_meta_key'] = sanitize_key( isset( $input['group_meta_key'] ) ? $input['group_meta_key'] : $defaults['group_meta_key'] );
            $output['actor_user_id'] = max( 0, (int) ( isset( $input['actor_user_id'] ) ? $input['actor_user_id'] : 0 ) );

            $allowed_fallbacks = array( 'profile', 'none' );
            $fallback = isset( $input['group_fallback'] ) ? sanitize_key( $input['group_fallback'] ) : $defaults['group_fallback'];
            if ( ! in_array( $fallback, $allowed_fallbacks, true ) ) {
                $fallback = 'profile';
            }
            $output['group_fallback'] = $fallback;

            return $output;
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
                echo '<div class="notice notice-success inline"><p>' . esc_html__( 'MarketPress-Integrations-Einstellungen gespeichert.', 'cp-community' ) . '</p></div>';
            }

            $s = $this->get_settings();
            ?>
            <div class="cpc-integration-box" style="border: 1px solid #ddd; padding: 20px; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px;">
                <h2><?php echo esc_html__( 'PS MarketPress Integration', 'cp-community' ); ?></h2>
                <p><?php echo esc_html__( 'Verknuepft Bestell-Ereignisse mit Profil-Tab, Benachrichtigungen und Aktivitaet in PS Community.', 'cp-community' ); ?></p>

                <?php if ( $status['active'] ) : ?>
                    <div class="notice <?php echo ! empty( $s['enabled'] ) ? 'notice-success' : 'notice-warning'; ?> inline" style="margin: 0 0 20px 0;">
                        <p>
                            <strong><?php echo esc_html__( 'MarketPress:', 'cp-community' ); ?></strong>
                            <span style="color: #155724;">✓ <?php echo esc_html__( 'Plugin aktiv', 'cp-community' ); ?></span>
                        </p>
                        <p style="margin: 5px 0 0 0;">
                            <strong><?php echo esc_html__( 'PS-Community-Integration:', 'cp-community' ); ?></strong>
                            <?php if ( ! empty( $s['enabled'] ) ) : ?>
                                <span style="color: #155724;">✓ <?php echo esc_html__( 'Aktiviert', 'cp-community' ); ?></span>
                            <?php else : ?>
                                <span style="color: #856404;">⚠ <?php echo esc_html__( 'Deaktiviert', 'cp-community' ); ?></span>
                            <?php endif; ?>
                        </p>
                        <p style="margin: 5px 0 0 0;">
                            <strong><?php echo esc_html__( 'Version:', 'cp-community' ); ?></strong>
                            <?php echo esc_html( $status['name'] ); ?> <?php echo esc_html( $status['version'] ); ?>
                        </p>
                    </div>

                <details class="mpcpc-integration-settings">
                    <summary style="cursor:pointer; font-weight:600; padding:8px 0;"><?php echo esc_html__( 'MarketPress-Integration konfigurieren', 'cp-community' ); ?></summary>
                    <div style="padding-top: 8px;">
                <form method="post" action="">
                    <?php wp_nonce_field( 'mpcpc_integration_settings' ); ?>

                    <h2><?php echo esc_html__( 'Allgemein', 'cp-community' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Integration aktivieren', 'cp-community' ); ?></th>
                            <td>
                                <input type="hidden" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="0">
                                <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?>> <?php echo esc_html__( 'MarketPress mit PS Community verbinden', 'cp-community' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Debug-Logging', 'cp-community' ); ?></th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[debug_log]" value="1" <?php checked( ! empty( $s['debug_log'] ) ); ?>> <?php echo esc_html__( 'Schreibt Events ins PHP-Errorlog', 'cp-community' ); ?></label></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Actor User-ID', 'cp-community' ); ?></th>
                            <td><input type="number" min="0" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[actor_user_id]" value="<?php echo esc_attr( (int) $s['actor_user_id'] ); ?>" class="small-text"> <p class="description"><?php echo esc_html__( '0 = Besteller/aktueller Benutzer als Autor fuer Alerts/Aktivitaet.', 'cp-community' ); ?></p></td>
                        </tr>
                    </table>

                    <h2><?php echo esc_html__( 'Profil-Tab', 'cp-community' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Tab aktivieren', 'cp-community' ); ?></th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_tab_enabled]" value="1" <?php checked( ! empty( $s['profile_tab_enabled'] ) ); ?>> <?php echo esc_html__( 'Mein-Bestellungen-Tab anzeigen', 'cp-community' ); ?></label></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Tab-Label', 'cp-community' ); ?></th>
                            <td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_tab_label]" value="<?php echo esc_attr( $s['profile_tab_label'] ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Tab-Prioritaet', 'cp-community' ); ?></th>
                            <td><input type="number" min="1" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_tab_priority]" value="<?php echo esc_attr( (int) $s['profile_tab_priority'] ); ?>" class="small-text"></td>
                        </tr>
                    </table>

                    <h2><?php echo esc_html__( 'Alerts', 'cp-community' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr><th scope="row"><?php echo esc_html__( 'Alerts aktivieren', 'cp-community' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alerts_enabled]" value="1" <?php checked( ! empty( $s['alerts_enabled'] ) ); ?>> <?php echo esc_html__( 'PS Community Alerts schreiben', 'cp-community' ); ?></label></td></tr>
                        <tr><th scope="row"><?php echo esc_html__( 'Empfaenger', 'cp-community' ); ?></th><td>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alert_to_buyer]" value="1" <?php checked( ! empty( $s['alert_to_buyer'] ) ); ?>> <?php echo esc_html__( 'Kaeufer', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alert_to_admin]" value="1" <?php checked( ! empty( $s['alert_to_admin'] ) ); ?>> <?php echo esc_html__( 'Admins', 'cp-community' ); ?></label>
                        </td></tr>
                        <tr><th scope="row"><?php echo esc_html__( 'Events', 'cp-community' ); ?></th><td>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alert_new_order]" value="1" <?php checked( ! empty( $s['alert_new_order'] ) ); ?>> <?php echo esc_html__( 'Neue Bestellung', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alert_paid]" value="1" <?php checked( ! empty( $s['alert_paid'] ) ); ?>> <?php echo esc_html__( 'Bezahlt', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alert_shipped]" value="1" <?php checked( ! empty( $s['alert_shipped'] ) ); ?>> <?php echo esc_html__( 'Versendet', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alert_closed]" value="1" <?php checked( ! empty( $s['alert_closed'] ) ); ?>> <?php echo esc_html__( 'Abgeschlossen', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[alert_trashed]" value="1" <?php checked( ! empty( $s['alert_trashed'] ) ); ?>> <?php echo esc_html__( 'Storniert/Entfernt', 'cp-community' ); ?></label>
                        </td></tr>
                    </table>

                    <h2><?php echo esc_html__( 'Aktivitaet', 'cp-community' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr><th scope="row"><?php echo esc_html__( 'Aktivitaet aktivieren', 'cp-community' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[activity_enabled]" value="1" <?php checked( ! empty( $s['activity_enabled'] ) ); ?>> <?php echo esc_html__( 'Aktivitaetsbeitraege schreiben', 'cp-community' ); ?></label></td></tr>
                        <tr><th scope="row"><?php echo esc_html__( 'Kanaele', 'cp-community' ); ?></th><td>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[activity_to_profile]" value="1" <?php checked( ! empty( $s['activity_to_profile'] ) ); ?>> <?php echo esc_html__( 'Profil-Kontext', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[activity_to_group]" value="1" <?php checked( ! empty( $s['activity_to_group'] ) ); ?>> <?php echo esc_html__( 'Gruppen-Kontext', 'cp-community' ); ?></label>
                        </td></tr>
                        <tr><th scope="row"><?php echo esc_html__( 'Events', 'cp-community' ); ?></th><td>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[activity_new_order]" value="1" <?php checked( ! empty( $s['activity_new_order'] ) ); ?>> <?php echo esc_html__( 'Neue Bestellung', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[activity_paid]" value="1" <?php checked( ! empty( $s['activity_paid'] ) ); ?>> <?php echo esc_html__( 'Bezahlt', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[activity_shipped]" value="1" <?php checked( ! empty( $s['activity_shipped'] ) ); ?>> <?php echo esc_html__( 'Versendet', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[activity_closed]" value="1" <?php checked( ! empty( $s['activity_closed'] ) ); ?>> <?php echo esc_html__( 'Abgeschlossen', 'cp-community' ); ?></label><br>
                            <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[activity_trashed]" value="1" <?php checked( ! empty( $s['activity_trashed'] ) ); ?>> <?php echo esc_html__( 'Storniert/Entfernt', 'cp-community' ); ?></label>
                        </td></tr>
                        <tr><th scope="row"><?php echo esc_html__( 'Privacy', 'cp-community' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[privacy_hide_amounts]" value="1" <?php checked( ! empty( $s['privacy_hide_amounts'] ) ); ?>> <?php echo esc_html__( 'Betrag in Meldungen ausblenden', 'cp-community' ); ?></label></td></tr>
                    </table>

                    <h2><?php echo esc_html__( 'Gruppenkontext', 'cp-community' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr><th scope="row"><?php echo esc_html__( 'Gruppenmapping aktivieren', 'cp-community' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[group_context_enabled]" value="1" <?php checked( ! empty( $s['group_context_enabled'] ) ); ?>> <?php echo esc_html__( 'Group-Kontext aus Produkt-Meta lesen', 'cp-community' ); ?></label></td></tr>
                        <tr><th scope="row"><?php echo esc_html__( 'Produkt-Meta-Key fuer Gruppe', 'cp-community' ); ?></th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[group_meta_key]" value="<?php echo esc_attr( $s['group_meta_key'] ); ?>" class="regular-text"> <p class="description"><?php echo esc_html__( 'Beispiel: cpc_group_id (enthaelt die ID einer cpc_group am Produkt).', 'cp-community' ); ?></p></td></tr>
                        <tr><th scope="row"><?php echo esc_html__( 'Fallback ohne Gruppe', 'cp-community' ); ?></th><td>
                            <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[group_fallback]">
                                <option value="profile" <?php selected( $s['group_fallback'], 'profile' ); ?>><?php echo esc_html__( 'Ins Profil schreiben', 'cp-community' ); ?></option>
                                <option value="none" <?php selected( $s['group_fallback'], 'none' ); ?>><?php echo esc_html__( 'Nicht schreiben', 'cp-community' ); ?></option>
                            </select>
                        </td></tr>
                    </table>

                    <?php submit_button( __( 'Einstellungen speichern', 'cp-community' ), 'primary', 'mpcpc_integration_save' ); ?>
                </form>
                    </div>
                </details>

                <?php elseif ( $status['installed'] ) : ?>
                    <div class="notice notice-warning inline" style="margin: 0 0 20px 0;">
                        <p>
                            <strong><?php echo esc_html__( 'Status:', 'cp-community' ); ?></strong>
                            <span style="color: #856404;">⚠ <?php echo esc_html__( 'Installiert aber deaktiviert', 'cp-community' ); ?></span>
                        </p>
                    </div>
                    <p><?php echo esc_html__( 'PS MarketPress ist installiert, aber nicht aktiviert. Um die Integration nutzen zu können, musst du das Plugin aktivieren.', 'cp-community' ); ?></p>
                    <p>
                        <a href="<?php echo esc_url( wp_nonce_url(
                            admin_url( 'plugins.php?action=activate&plugin=' . $status['file'] ),
                            'activate-plugin_' . $status['file']
                        ) ); ?>" class="button button-primary">
                            <?php echo esc_html__( 'PS MarketPress aktivieren', 'cp-community' ); ?>
                        </a>
                    </p>
                <?php else : ?>
                    <div class="notice notice-info inline" style="margin: 0 0 20px 0;">
                        <p>
                            <strong><?php echo esc_html__( 'Status:', 'cp-community' ); ?></strong>
                            <span style="color: #0c5460;">ℹ <?php echo esc_html__( 'Nicht installiert', 'cp-community' ); ?></span>
                        </p>
                    </div>
                    <p><?php echo esc_html__( 'PS MarketPress ist nicht installiert. Installiere zuerst das MarketPress-Plugin, um diese Integration nutzen zu können.', 'cp-community' ); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }

        private function debug( $message ) {
            $settings = $this->get_settings();
            if ( empty( $settings['debug_log'] ) ) {
                return;
            }
            error_log( '[MPCPC] ' . $message );
        }
    }
}

new PS_MarketPress_Community_Bridge();
