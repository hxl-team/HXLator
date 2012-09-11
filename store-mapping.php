<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');

include_once('functions.php');

if(isset($_SESSION['loggedin'])) {   // only submit data if the user is logged in:
	
	$path = getcwd().'/mappings/'.$_SESSION['user_shortname'];
	
	// create user folder if it doesn't exist yet:
	if(!is_dir($path)){
		if(!mkdir($path)){
			echo '<p>Your user directory could not be initialized. Please <a href="contact.php">let us know</a> about this error.</p>';
		}
	}

	$filename = $_SESSION['file'].' ('.date('Y-m-d \a\t H\hi').')';
	$flush = $path.'/'.$filename.'.json';

	if(file_put_contents($flush, $_POST['mapping']) === false){
		echo '<p>Saving your translator failed, sorry about that. Please <a href="contact.php">let us know</a> about this error.</p>';
	}else{
		echo '<p>The translator you have built during this session has been saved as<br /><code>'.$filename.'</code>.<br /> If you want to HXLate a spreadsheet with the same structure again later, you can select this mapping on the upload page.</p>';
	}
	

}else{
	echo 'You have to log in to submit data.';
}  

?>