<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');
getHead(); 



?>

<div class="container">
	<div class="hero-unit">	    
		<a href="https://sites.google.com/site/hxlproject/"><img src="img/hxl-logo-s.png" align="right" /></a>
		<h1>HXLator</h1>
	    <p style="margin-top:  1.5em; margin-bottom:  1.5em">A simple online tool to convert <em>any</em> spreadsheet into the 
	    Humanitarian eXchange Language (HXL). Take a look at our <a href="guide.php">quick start guide</a> or start by uploading your spreadsheet here:</p>	    
		<form enctype="multipart/form-data" action="hxlate.php" method="POST"  class="alert">
			<input type="hidden" name="MAX_FILE_SIZE" value="5242880"><!-- 5MB -->
			<input name="userfile" type="file">
			<button type="submit" class="btn">HXLate File</button>
		</form>  
	</div>	   
</div> <!-- /container -->


<?php getFoot(); ?>