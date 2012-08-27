<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');
getHead("manage.php"); 



?>

<div class="container">
	<a href="https://sites.google.com/site/hxlproject/"><img src="img/hxl-logo-s.png" align="right" /></a>
	<h1>Manage Translators</h1>

<?php if(isset($_SESSION['loggedin'])) {   // only hxlate if the user is logged in:
	echo '<p style="margin-top:  1.5em; margin-bottom:  1.5em"><span class="label label-warning">TBD</span></p>';
}else{
	show_login_form();
} 

?>

</div> <!-- /container -->

<?php getFoot(); ?>