<?php 

/* If this file is moved, the followed path needs to be altered to point at root of WordPress installation */
include_once('../../../wp-load.php');
global $wpdb;

$code = isset($_GET['code']) && $_GET['code'] && $_GET['code'] != '' ? $_GET['code'] : 'no API security code passed';

echo '<cpc>';
echo '<version>0.1</version>';

if (cpc_api_correct($code)):

	$api = isset($_GET['api']) ? $_GET['api'] : 'no API function passed';

	if (cpc_api_function_permitted($api)):

		if ($api == 'get_all_users'):

			// Return details of all users as
			// user->user_login
			// user->display_name

			$sql = "select * from {$wpdb->prefix}users order by user_login";
			$users = $wpdb->get_results($sql);

			echo '<users>';
				foreach ($users as $user):
					echo '<user>';
						echo '<user_login>'.$user->user_login.'</user_login>';
						echo '<display_name>'.$user->display_name.'</display_name>';
					echo '</user>';
				endforeach;
			echo '</users>';

		else:

			echo '<error>';
				echo '<name>Falsche API-Funktion ('.$api.')</name>';
			echo '</error>';

		endif;

	else:

		echo '<error>';
			echo '<name>Falsche API-Funktion oder nicht aktiviert ('.$api.')</name>';
		echo '</error>';

	endif;

else:

	echo '<error>';
		echo '<name>Falscher API-Sicherheitscode ('.htmlspecialchars($code).')</name>';
	echo '</error>';

endif;

echo '</cpc>';

?>