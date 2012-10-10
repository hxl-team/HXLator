<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');

if(isset($_SESSION['loggedin'])) {   // only submit data if the user is logged in:
	
	// load password for triple store from file:
	$login = file_get_contents('../../store.txt');
	$container = 'no container found';
	
  // look for the data container in the RDF code to know which named graph we're posting to:
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $_POST['hxl']) as $line){
    	if(strpos($line, 'a <http://hxl.humanitarianresponse.info/ns/#DataContainer>') !== false){
    		$container = explode(' ', $line);
        $container = $container[0]; // subject of this triple
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
  $status = curl_getinfo($post, CURLINFO_HTTP_CODE);
 	curl_close($post);

 	// echo '<a href="http://sparql.carsten.io/?query=SELECT%20*%20WHERE%20%7B%0A%20%20GRAPH%20%3C'.str_replace(':', '%3A', $container).'%3E%20%7B%0A%20%20%20%20%3Fa%20%3Fb%20%3Fc%20.%0A%20%20%7D%0A%7D&endpoint=http%3A//hxl.humanitarianresponse.info/sparql" class="btn btn-info" target="_blank">Check the submitted data</a>';
  
  if(strpos(strval($status), '4') === 0 ){ // error - http code 4XX
    echo '<p class="lead"><span class="label label-important">Your upload failed.</span> Server message: '.$result.'</p>';
  } else { // okay
    echo '<p class="lead">The data container <code>'.$container.'</code> has been submitted for <a href="approve.php">approval</a>.</p>';
  }


}else{
	echo '<p class="lead"><span class="label label-important">You have to log in to submit data.</span></p>';
}  

?>