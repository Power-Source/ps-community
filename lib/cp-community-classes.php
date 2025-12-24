<?php
//echo 'this is the library file';
function display_date ($date_format, $the_post_ID, $the_post_date) {

	if (strstr($date_format,"%s")):
		$date = sprintf($date_format, human_time_diff(strtotime($the_post_date), current_time('timestamp', 1)));
	elseif (strlen($date_format)): //TWC - There is a formatted string
		$date = get_the_time($date_format, $the_post_ID);
	endif;

	return $date;
}

?>