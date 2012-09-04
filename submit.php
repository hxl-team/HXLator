<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');

if(isset($_SESSION['loggedin'])) {   // only hxlate if the user is logged in:
	
	$tripleStorePW = file_get_contents('../../store.txt');
	$container = 'no container found';
	
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $_POST['hxl']) as $line){
    	if(strpos($line, 'a <http://hxl.humanitarianresponse.info/ns/#DataContainer>') !== false){
    		$container = explode(' ', $line)[0]; // subject of this triple
    		$container = substr($container, 1, -1);
    	}
	}
	

	echo 'Data submitted via '.$container;

	// submission via cURL
	// $post = curl_init();
 
 //   	curl_setopt($post, CURLOPT_URL, $url);
 //   	curl_setopt($post, CURLOPT_POST, TRUE);
 //   	curl_setopt($post, CURLOPT_POSTFIELDS, $_POST['hxl']);
 //   	curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
 
	// $result = curl_exec($post);
 
 //   	curl_close($post);


}else{
	echo 'You need to be logged in to submit data.';
} 

 


?>

