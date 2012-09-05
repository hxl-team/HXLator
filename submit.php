<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');

if(isset($_SESSION['loggedin'])) {   // only submit data if the user is logged in:
	
	// load password for triple store from file:
	$login = file_get_contents('../../store.txt');
	$container = 'no container found';
	
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $_POST['hxl']) as $line){
    	if(strpos($line, 'a <http://hxl.humanitarianresponse.info/ns/#DataContainer>') !== false){
    		$container = explode(' ', $line)[0]; // subject of this triple
    		$container = substr($container, 1, -1);
    	}
	}
	

	// submission via cURL
	$post = curl_init();
 
   	curl_setopt($post, CURLOPT_URL, 'http://hxl.humanitarianresponse.info/incubator-graphstore?graph='.$container);
   	curl_setopt($post, CURLOPT_POST, TRUE);
   	curl_setopt($post, CURLOPT_USERPWD, $login);
   	curl_setopt($post, CURLOPT_HTTPHEADER, array('Content-Type: application/x-turtle'));
   	curl_setopt($post, CURLOPT_POSTFIELDS, $_POST['hxl']);
   	curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
 
	$result = curl_exec($post);
 
   	curl_close($post);

   	echo 'The data container<br /><code>'.$container.'</code><br />has been submitted for approval.';


}else{
	echo 'You have to log in to submit data.';
}  

?>