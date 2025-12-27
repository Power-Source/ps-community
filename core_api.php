<?php
/* ****************** */ /* CORE API FUNCTIONS */ /* ****************** */
/**
 * Gets last active timestamp for a user, optionally formatted
 *
 * @since 14.12.2
 *
 * @param   int     $user_id       The ClassicPress user ID
 * @param   mixed   $format        Set to false to return date/time value, or a string for formatting, eg: "Last active: %s ago"
 *
 * @return  mixed   Date/time value, formatted string, or false if no last active value available
 */
function cpc_api_user_last_active($user_id, $format=false) {
    $datetime = false;
    $last_active = get_user_meta($user_id, 'cpccom_last_active', true);
    if ($last_active):
        if (!$format):
            $datetime = $last_active;
        else:
			      $datetime = sprintf($format, human_time_diff(strtotime($last_active), current_time('timestamp', 1)));
        endif;
    endif;
    return $datetime;
}


/**
 * Fügt einen ClassicPress-Beitrag des Typs cpc_activity ein
 *
 * @since 14.12.2
 *
 * @param   string  $activity_post Der Aktivitätspost, der eingefügt werden soll
 * @param   int     $the_author_id ID eines ClassicPress-Mitglieds als Autor des Aktivitätsposts
 * @param   int     $the_target_id ID eines ClassicPress-Mitglieds als Ziel des Aktivitätsposts (verwende $the_author_id für Post an sich selbst/Freunde)
 * @param   array   $the_post      Optional $_POST zur weiteren Verarbeitung durch cpc_activity_post_add_hook
 * @param   array   $the_files     Optional $_FILES zur weiteren Verarbeitung durch cpc_activity_post_add_hook
 *
 * @return  int     ID des neuen ClassicPress-Beitrags oder false, wenn das Einfügen fehlgeschlagen ist
 *
 * Hinweis: Dies beinhaltet den Hook cpc_activity_post_add_hook, sodass Benachrichtigungen generiert werden können
 */
function cpc_api_insert_activity_post($activity_post, $the_author_id, $the_target_id, $the_post=null, $the_files=null) {
                                  
	global $current_user;
    
    $new_id = false;

	if ( is_user_logged_in() ) {

        $post = array(
          'post_title'     => $activity_post,
          'post_status'    => 'publish',
          'post_type'      => 'cpc_activity',
          'post_author'    => $the_author_id,
          'ping_status'    => 'closed',
          'comment_status' => 'open',
        );  
        $new_id = wp_insert_post( $post );

        if ($new_id):
            update_post_meta( $new_id, 'cpc_target', $the_target_id );
            do_action( 'cpc_activity_post_add_hook', $the_post, $the_files, $new_id );
        endif;

	}

    return $new_id;
}

?>