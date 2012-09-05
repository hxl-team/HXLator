<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');

if(isset($_SESSION['loggedin'])) {   // only delete a container if the user is logged in AND has the approver role:
	
  	if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'approver'){
	  	// load password for triple store from file:
	  	$login = file_get_contents('../../store.txt');
	  	$container = $_GET['container'];
	  	
	  	// delete via cURL
	  	$delete = curl_init();
	   
	    curl_setopt($delete, CURLOPT_URL, 'http://hxl.humanitarianresponse.info/incubator-graphstore?graph='.$container);
	    curl_setopt($delete, CURLOPT_USERPWD, $login);
	    curl_setopt($delete, CURLOPT_CUSTOMREQUEST, 'DELETE');
	    
	  	if(curl_exec($delete)){
	      echo 'Container <code>'.$container.'</code> deleted.';
	    }else{
	      echo 'Ooops. Something went wrong.';
	    }
	   
	  	curl_close($delete);

  	} else{
		echo '<span class="label label-warning">Not allowed.</span> Please log in with a user name that is authorized to approve data to proceed.';	
	}

}else{
	echo 'You have to log in to delete data containers.';
}  

?>