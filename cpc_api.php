<?php 

/* If this file is moved, the followed path needs to be altered to point at root of ClassicPress installation */
include_once('../../../wp-load.php');
global $wpdb;

// Set XML content type and prevent caching
header('Content-Type: text/xml; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$code = isset($_GET['code']) && $_GET['code'] !== '' ? $_GET['code'] : 'no API security code passed';

echo '<cpc>';
echo '<version>0.1</version>';

if (cpc_api_correct($code)):

	$api = isset($_GET['api']) ? $_GET['api'] : 'no API function passed';

	if (cpc_api_function_permitted($api)):

		if ($api == 'get_all_users'):

			// Return details of all users as
			// user->user_login
			// user->display_name
			// Limit to 500 to prevent unbounded data dumps
			$sql = "SELECT user_login, display_name FROM {$wpdb->prefix}users ORDER BY user_login LIMIT 500";
			$users = $wpdb->get_results($sql);

			echo '<users>';
				foreach ($users as $user):
					echo '<user>';
						echo '<user_login>'.esc_xml($user->user_login).'</user_login>';
						echo '<display_name>'.esc_xml($user->display_name).'</display_name>';
					echo '</user>';
				endforeach;
			echo '</users>';

		else:

			echo '<error>';
				echo '<name>Falsche API-Funktion ('.esc_xml($api).')</name>';
			echo '</error>';

		endif;

	else:

		echo '<error>';
			echo '<name>Falsche API-Funktion oder nicht aktiviert ('.esc_xml($api).')</name>';
		echo '</error>';

	endif;

else:

	echo '<error>';
		echo '<name>Falscher API-Sicherheitscode ('.htmlspecialchars($code).')</name>';
	echo '</error>';

endif;

echo '</cpc>';

?>