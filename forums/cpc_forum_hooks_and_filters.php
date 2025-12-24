<?php

// Add automatic subscriptions for any forums
add_action('user_register','cpc_forum_auto_subscribe');
function cpc_forum_auto_subscribe($user_id){

    if (function_exists('cpc_forum_subs_extension_insert_rewrite_rules')):
    
        $terms = get_terms( "cpc_forum", array(
            'hide_empty'    => false, 
            'fields'        => 'all', 
            'hierarchical'  => false, 
        ) );

        if ($terms):

            foreach ($terms as $term):

                if ( cpc_get_term_meta($term->term_id, 'cpc_forum_auto', true) ):

                    $user = get_user_by('id', $user_id);
                    $post = array(
                        'post_title'		=> $user->user_login,
                        'post_status'   	=> 'publish',
                        'post_type'     	=> 'cpc_forum_subs',
                        'post_author'   	=> $user->ID,
                        'ping_status'   	=> 'closed',
                        'comment_status'	=> 'closed',
                    );  
                    $new_sub_id = wp_insert_post( $post );
                    update_post_meta( $new_sub_id, 'cpc_forum_id', $term->term_id );

                endif;

            endforeach;

        endif;
    
    endif;
    
}

add_action('wp_head', 'cpc_forum_sharethis_head');
function cpc_forum_sharethis_head() {
    $js = get_option('cpc_forum_sharethis_js');
    if ($js) echo $js;
}

?>