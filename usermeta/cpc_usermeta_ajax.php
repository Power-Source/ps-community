<?php
// AJAX functions for activity
add_action( 'wp_ajax_cpc_get_countries', 'cpc_get_countries' ); 
add_action( 'wp_ajax_cpc_get_get_cities', 'cpc_get_get_cities' ); 

// Get countries
function cpc_get_countries() {
	
	global $wpdb;
	$term = isset($_POST['term']) ? $_POST['term'] : '';
    
	$sql = "SELECT id, country FROM ".$wpdb->base_prefix."cpc_countries
            WHERE ( 
                country like '%%%s%%'
            )
            ORDER BY country";
	$rows = $wpdb->get_results($wpdb->prepare($sql, $term));
    
	$return_arr = array();
	foreach ($rows as $row) {
	    $row_array['value'] = $row->id;
	    $row_array['label'] = $row->country;
	    array_push($return_arr,$row_array);
	}
	echo json_encode($return_arr);	
	exit;
	
}

function cpc_get_get_cities() {
  
	global $wpdb;
	$term = isset($_POST['term']) ? $_POST['term'] : '';
	$country_id = isset($_POST['country_id']) ? $_POST['country_id'] : '';
    
	$sql = "SELECT id, city FROM ".$wpdb->base_prefix."cpc_cities
            WHERE ( 
                city like '%%%s%%'
            ) AND (
                country_id = %d
            )
            ORDER BY city";
	$rows = $wpdb->get_results($wpdb->prepare($sql, $term, $country_id));
    
	$return_arr = array();
	foreach ($rows as $row) {
	    $row_array['value'] = $row->id;
	    $row_array['label'] = $row->city;
	    array_push($return_arr,$row_array);
	}
	echo json_encode($return_arr);	
	exit;
    
}

?>
