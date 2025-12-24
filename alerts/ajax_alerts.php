<?php
// AJAX functions for crowds
add_action( 'wp_ajax_cpc_alerts_activity_redirect', 'cpc_alerts_activity_redirect' ); 
add_action( 'wp_ajax_cpc_alerts_make_all_read', 'cpc_alerts_make_all_read' ); 
add_action( 'wp_ajax_cpc_alerts_list_item_delete', 'cpc_alerts_list_item_delete' ); 
add_action( 'wp_ajax_cpc_alerts_delete_all', 'cpc_alerts_delete_all' ); 


/* DELETE ALL ALERTS */
function cpc_alerts_delete_all() {
    
    global $current_user;
    if ( $current_user ) {    

        global $current_user;
        $args = array(
            'posts_per_page'   => -1,
            'orderby'          => 'post_date',
            'order'            => 'DESC',
            'post_type'        => 'cpc_alerts',
            'post_status'      => array('publish', 'pending'),
            'meta_query' => array(
                array(
                    'key' => 'cpc_alert_recipient',
                    'value' => $current_user->user_login,
                    'compare' => '=='
                )
            )
        );
        $alerts = get_posts($args);
        if ($alerts):
            foreach ($alerts as $alert):
                wp_delete_post($alert->ID, true);
            endforeach;
        endif;
    }
    
    exit();
}

/* DELETE ALERT */
function cpc_alerts_list_item_delete() {
    
    global $current_user;
    if ( $current_user ) {      

        wp_delete_post($_POST['alert_id'], true);
        
    }
    
    exit();

}

/* MARK ALERT AS READ */
function cpc_alerts_activity_redirect() {
    
    global $current_user;
    if ( $current_user ) {      

        if ($_POST['delete_alert'] != '1'):
            update_post_meta( $_POST['alert_id'], 'cpc_alert_read', true );
        else:
            wp_delete_post($_POST['alert_id'], true);
        endif;

        echo $_POST['url'];
        
    }
    
    exit();
        

}

/* MARK ALL ALERTS AS READ */
function cpc_alerts_make_all_read() {

    global $current_user;
    if ( $current_user ) {  
        
        $args = array(
            'posts_per_page'   => -1,
            'orderby'          => 'post_date',
            'order'            => 'DESC',
            'post_type'        => 'cpc_alerts',
            'post_status'      => array('publish', 'pending'),
            'meta_query' => array(
                array(
                    'key' => 'cpc_alert_recipient',
                    'value' => $current_user->user_login,
                    'compare' => '=='
                )
            )
        );
        $alerts = get_posts($args);
        if ($alerts):
            foreach ($alerts as $alert):
                update_post_meta( $alert->ID, 'cpc_alert_read', true );
            endforeach;
        endif;
        
    }

    exit();

}

?>
