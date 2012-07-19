<?php 

set_include_path('Classes/');

require_once "EasyRdf.php";
require_once "html_tag_helpers.php";

// fires the $query against our SPARQL endpoint and returns a EasyRDF Sparql_Result object 
// (see http://www.aelius.com/njh/easyrdf/docs/EasyRdf/EasyRdf_Sparql_Result.html),
// which is basically an ArrayIterator with some extras
function sparqlQuery($query){

	// these prefixes will be added to every SPARQL query - so no need to declare them over and 
	// over again - please DO NOT add prefixes to the actual queries, this might mess things up 
	// if we end up defining the same prefixes several times. Just put additional prefix 
	// declarations here.
	$prefixes = 'prefix xsd: <http://www.w3.org/2001/XMLSchema#>  
	prefix skos: <http://www.w3.org/2004/02/skos/core#> 
	prefix hxl:   <http://hxl.humanitarianresponse.info/ns/#> 
	prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
	prefix label: <http://www.wasab.dk/morten/2004/03/label#> 
	
	';


  	$sparql = new EasyRdf_Sparql_Client('http://hxl.humanitarianresponse.info/sparql');
  	$query = $prefixes.$query;
//  	error_log($query);
  
  	try {
    	$results = $sparql->query($query);      
      	return $results;
  	} catch (Exception $e) {
      	return "<div class='error'>".$e->getMessage()."</div>\n";
  	}
}

// this function implements the URI patterns documented at 
// https://docs.google.com/document/d/1-9OoF5vz71qPtPRo3WoaMH4S5J1O41ITszT3arQ5VLs/edit
// depending on the $type, the function will pick the corresponding pattern and return a valid URI. 
// if the $type requires further properties to be known, these have to be passed on 
// as an associative array in $properties; dig into the code to see which patterns support which properties:
function makeURI($type, $properties = null){
	
	$base = "http://hxl.humanitarianresponse.info/data/";
	
	if($type == "hxl:DataContainer"){
		
		$time = gettimeofday();
		$id = $time['sec'].'.'.$time['usec'];
		
		$org = "";
		
		if($properties["org"]){
			$org = strtolower($properties["org"])."/";
		}
		
		return $base."datacontainers/".$org.$id;

	} else {   // todo: other patterns
	
		$time = gettimeofday();
		$id = $time['sec'].'.'.$time['usec'];
		
		return "http://example.com/".$id;
		error_log("Dummy URI generated for unknown resource type ".$type);
	}
}




// generates the head for all pages, including highlighting of the activr page in the nav bar
function getHead($activePage = "index.php", $user = null, $org = null){  

$links = array("index.php" => "HXLate", 
			   "guide.php" => "Quick Start Guide",
			   "faq.php" => "FAQ",
			   "contact.php" => "Contact"); 


echo'<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>OCHA HXLator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <link href="css/hxlator.css" rel="stylesheet"> 
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
	<link href="css/jquery-ui-1.8.21.custom.css" rel="stylesheet">
	
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    
    
    <link rel="shortcut icon" href="img/ochaonline_theme_favicon.ico">
  </head>

  <body>

    <div class="navbar navbar-fixed-top">
        <div class="container">
          <span class="brand"><img src="img/logo.png" /></span>
          <div class="nav-hxlator">
            <ul class="nav" id="topnav">
'; 

foreach($links as $link => $text){
	if($link === $activePage){
		echo'
			<li class="active"><a href="'.$link.'">'.$text.'</a></li>';
	}else{
		echo'
			<li><a href="'.$link.'">'.$text.'</a></li>';	
	}
}

echo '
			<li class="logininfo"><a href="#">Logged in as '.$user.', '.$org.'</a></li>';

echo'           
            </ul>
          </div>
      </div>
    </div>    

'; 

} 


// creates the footer for the page, including the JS to load
// $extraJS can point to any extra js plugins in the /js folder 
// that are required by the page that loads this header
// amd an option to include an inline $script
function getFoot($extraJS = null, $script = null){ 

	echo'	<div class="container footer">
		<div class="row">
		  <div class="span3"><strong>Contact</strong><br />
		  This site is part of the HumanitarianResponse network. Write to 
		  <a href="mailto:info@humanitarianresponse.info">info@humanitarianresponse.info</a> for more information.</div>
		  <div class="span3"><strong>Links</strong><br />
		  <a href="https://sites.google.com/site/hxlproject/">HXL Project</a><br />
		  <a href="http://hxl.humanitarianresponse.info/">HXL Standard</a></div>
		  <div class="span3"><strong>Follow HXL</strong><br />
		  TBD</div>
		  <div class="span3"><strong>Legal</strong><br />
		  &copy; 2012 UNOCHA</div>
		</div>
	</div>
	
    <script src="js/jquery.js"></script> 
  ';
    
    if($extraJS){
    	foreach ($extraJS as $js) {
 	    	echo '
    		<script src="js/'.$js.'"></script>
    		';    
    	}
    }
    
    
    if($script){
    	echo'
    	<script type="text/javascript">
    	'.$script.'
    	</script>';
    }
 	
	echo'  
	</body>
</html>';

} 


// Generates the empty modal (see http://twitter.github.com/bootstrap/javascript.html#modals ) that 
// we'll be using to preview the HXL code
function loadModal(){
	echo '
	<div id="hxlPreview" class="modal hide fade">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal">×</button>
              <h3>HXL Preview<img src="img/nakedturtle.png" class="pull-right"></h3>
              <p>A preview of the HXL code generated from your spread sheet in <a href="http://en.wikipedia.org/wiki/Turtle_(syntax)">Turtle syntax</a>.</p>
            </div>
            <div class="modal-body">
              <code>
              	<pre id="nakedturtle"></pre>
              </code>
            </div>
            <div class="modal-footer">
              <a href="#" class="btn" data-dismiss="modal">Close</a>
              </div>
          </div>
	';
}



?>

