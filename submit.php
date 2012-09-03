<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');
getHead("manage.php"); 

if(isset($_SESSION['loggedin'])) {   // only hxlate if the user is logged in:
	
	

	// TODO: submission via cURL

}else{
	echo 'You need to be logged in to submit data.';	show_login_form();
} 

?>

