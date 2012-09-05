<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');
getHead("approve.php"); 



?>

<div class="container">
	<a href="https://sites.google.com/site/hxlproject/"><img src="img/hxl-logo-s.png" align="right" /></a>
	<h1>Approve submitted data containers</h1>

<?php if(isset($_SESSION['loggedin'])) {   
	// only allow the user to approve stuff if s/he has the according role:
	if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'approver'){

		// load password for triple store from file:
		$login = file_get_contents('../../store.txt');

		$query = 'SELECT * WHERE {?container a <http://hxl.humanitarianresponse.info/ns/#DataContainer> }';
		$endpoint = 'http://hxl.humanitarianresponse.info/incubator-sparql';
		// query via cURL, because we have to send the password along:
		$curl = curl_init();
 
   		curl_setopt($curl, CURLOPT_URL, $endpoint.'?query='.urlencode($query));
   		curl_setopt($curl, CURLOPT_USERPWD, $login);
   		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/sparql-results+json'));
   		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
 
		$response = curl_exec($curl);
 		
 		curl_close($curl);

 		$results = json_decode($response, true);

 		if(count($results['results']['bindings']) > 0){

 			echo '<p class="lead">The following data containers have been submitted to the incubator triple store for approval:<p>';

 			foreach ($results['results']['bindings'] as $index => $result) {
 				$container = $result['container']['value'];
 				echo '<p class="lead" container="'.$container.'"><code>'.$container.'</code> <a href="#" class="btn btn-success approve" container="'.$container.'">Approve</a> <a href="#" class="btn btn-danger delete" container="'.$container.'">Delete</a></p>';
 			}

 		} else {
 			
 			echo ' <p class="lead">There are currently no data containers in the incubator triple store pending approval.<p>';

 		}

 		

   		

	}else{
		echo '<p style="margin-top:  1.5em; margin-bottom:  1.5em"><span class="label label-warning">Not allowed.</span> Please log in with a user name that is authorized to approve data to proceed.</p>';	
	}
	
}else{
	show_login_form();
} 

?>

</div> <!-- /container -->

<?php 

$inlineScript = '

// click listener for delete buttons
$("a.delete").click(function(){
	$("#loading").show();
	var $container = $(this).attr("container");
	$("p[container=\""+$container+"\"]").slideUp(function(){
		$("p[container=\""+$container+"\"]").load("container-delete.php?container="+$container, function(){
			$("p[container=\""+$container+"\"]").slideDown();
			$("#loading").hide();
		});
	});
});

// click listener for approve buttons
$("a.approve").click(function(){
	$("#loading").show();
	var $container = $(this).attr("container");
	$("p[container=\""+$container+"\"]").slideUp(function(){
		$("p[container=\""+$container+"\"]").load("container-approve.php?container="+$container, function(){
			$("p[container=\""+$container+"\"]").slideDown();
			$("#loading").hide();
		});
	});
});';

getFoot(array(), $inlineScript); 

?>