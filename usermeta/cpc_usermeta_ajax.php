<?php
// AJAX functions for usermeta country/city lookups (requires login via wp_ajax_)
add_action( 'wp_ajax_cpc_get_countries', 'cpc_get_countries' );
add_action( 'wp_ajax_cpc_get_get_cities', 'cpc_get_get_cities' );

// Get countries
function cpc_get_countries() {
	check_ajax_referer( 'cpc-usermeta-nonce', 'security' );

	global $wpdb;
	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

	$sql  = "SELECT id, country FROM {$wpdb->base_prefix}cpc_countries
			 WHERE country LIKE %s
			 ORDER BY country
			 LIMIT 50";
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, '%' . $wpdb->esc_like( $term ) . '%' ) );

	$return_arr = array();
	foreach ( $rows as $row ) {
		$return_arr[] = array(
			'value' => (int) $row->id,
			'label' => $row->country,
		);
	}
	wp_send_json( $return_arr );
}

function cpc_get_get_cities() {
	check_ajax_referer( 'cpc-usermeta-nonce', 'security' );

	global $wpdb;
	$term       = isset( $_POST['term'] )       ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	$country_id = isset( $_POST['country_id'] ) ? absint( $_POST['country_id'] ) : 0;

	$sql  = "SELECT id, city FROM {$wpdb->base_prefix}cpc_cities
			 WHERE city LIKE %s
			 AND   country_id = %d
			 ORDER BY city
			 LIMIT 50";
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, '%' . $wpdb->esc_like( $term ) . '%', $country_id ) );

	$return_arr = array();
	foreach ( $rows as $row ) {
		$return_arr[] = array(
			'value' => (int) $row->id,
			'label' => $row->city,
		);
	}
	wp_send_json( $return_arr );
}

?>
