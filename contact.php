<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');
getHead("contact.php"); 



?>

<div class="container lead">
	<h1>Contact <i class="icon-envelope"></i></h1>
	<p style="margin-top:  1.5em; margin-bottom:  1.5em">For any questions or feedback around HXLator, feel free to <a href='mailto:hendrix@un.org,me@carsten.io?subject=HXLator'>get in touch with us</a>. In case you are having problems with a specific spreadsheet, please attach it to your email. For general discussions about HXL, please refer to our <a href="https://groups.google.com/forum/?fromgroups#!forum/hxlproject">Google group</a>.</p>	 
</div> <!-- /container -->

<?php getFoot(); ?>