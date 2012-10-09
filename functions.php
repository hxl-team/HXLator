<?php 

set_include_path('Classes/');

require_once "EasyRdf.php";
require_once "html_tag_helpers.php";

//handle the logins data TODO: fake for now, needs to be revised:
session_start();
// if(isset($_POST["user_name"]) && isset($_POST["user_organisation"])){
// 	$_SESSION["user_name"] = $_POST["user_name"];
// 	$_SESSION["user_organisation"] = $_POST["user_organisation"];
// }

// end session and force user to re-login after 60 minutes (3600 sec.):
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    // last request was more than 30 minates ago
    session_destroy();   // destroy session data in storage
    session_unset();     // unset $_SESSION variable for the runtime
}
$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp



// check login data, if there are any:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
   if(isset($_POST['logout'])){
      if($_POST['logout'] == 'logout'){
         unset($_SESSION['loggedin']);
         session_destroy();
      }      
   }else{

      // you'll need a file with a salt to enable this script to work (see http://www.php.net/manual/de/function.crypt.php).
      // PLEASE PLACE salt.txt OUTSIDE OF YOUR SERVER DIRECTORY!
      $salt = file_get_contents('../../salt.txt');

      // open file with passwords:

      // users.txt needs to be structured the following way (each line):
      // user,encryptedpassword,role,fullname,organisation,useruri,organisationuri
      // note that there are no spaces after the commas!
      // PLEASE PLACE users.txt OUTSIDE OF YOUR SERVER DIRECTORY!
      $row = 1;
      if (($handle = fopen("../../users.txt", "r")) !== FALSE) {
         while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            
            $row++;
            for ($c=0; $c < count($data); $c++) {
               // check user name and password
              if(isset($_POST['user'])){
                if ($_POST['user'] == $data[0] && $data[1] == substr(crypt($_POST['pass'], $salt), strlen($salt))) {
                  $_SESSION['loggedin'] = true;
                  $_SESSION['user_shortname'] = $data[0];
                  $_SESSION["user_role"] = $data[2];
                  $_SESSION["user_name"] = $data[3];
                  $_SESSION["user_organisation"] = $data[4];
                  $_SESSION["user_uri"] = $data[5];
                  $_SESSION["user_organisation_uri"] = $data[6];
                } 
              }
               
            }
         }
         fclose($handle);
      }
   }
}

// show the login form, wwith an automatically generated error msg if the user has provided a wrong username/password combination
function show_login_form() {
    
?>
    
<form class="form-horizontal" action="index.php" method="POST">
    
<?php
    if(isset($_POST['user']) && isset($_POST['pass']) && !isset($_SESSION['loggedin'])){
        echo '<legend><p><span class="label label-important" style="font-size: 1em; font-weight: normal">Wrong username or password.</span></p><p>Please try again.</p></legend>';
    }else if(isset($_POST['logout'])){
        if($_POST['logout'] == 'logout'){
            echo '<legend><p><span class="label label-info" style="font-size: 1em; font-weight: normal">You are now logged out.</span></p></legend>';
        }
    }else{
        echo '<legend>Please log in:</legend>';
    }
?>
  <div class="control-group">
    <label class="control-label" for="user">User name</label>
    <div class="controls">
      <input type="text" id="user" name="user" placeholder="User name">
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="pass">Password</label>
    <div class="controls">
      <input type="password" id="pass" name="pass" placeholder="Password">
    </div>
  </div>
  <div class="control-group">
    <div class="controls">
      <button type="submit" class="btn">Log in</button>
    </div>
  </div>
</form>

<?php
}

// fires the $query against the given SPARQL endpoint and returns an EasyRDF Sparql_Result object 
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
		//error_log("Dummy URI generated for unknown resource type ".$type);
	}
}




// generates the head for all pages, including highlighting of the activr page in the nav bar
function getHead($activePage = "index.php"){  

$links = array("index.php" => "HXLate", 
			   "manage.php" => "Manage Translators",
         "approve.php" => "Approve Data",
			   "guide.php" => "Quick Start Guide",
			   "contact.php" => "Contact"); 


echo'<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>OCHA HXLator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta http-equiv="expires" content="0"> <!-- disable cache -->

    <link href="css/hxlator.css" rel="stylesheet"> 
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
	<link href="css/jquery-ui-1.8.21.custom.css" rel="stylesheet">
	
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    
    
    <link rel="shortcut icon" href="img/ochaonline_theme_favicon.ico">
  </head>

  <body>
	<div class="container">
	<div class="navbar">
        <div class="container">
          <span class="brand"><img src="img/loader.gif" id="loading" class="pull-right" /><img src="img/logo.png" /></span>
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

if(isset($_SESSION['loggedin'])){
    echo '
            <li class="logininfo" style="padding-top: 10px">Logged in as <span data-hxl-uri="'.$_SESSION["user_uri"].'" id="user-uri">'.$_SESSION["user_name"].'</span>, '.$_SESSION["user_organisation"].'</li>
            <li><form class="form-inline" action="index.php" method="post">
                <input type="hidden" name="logout" value="logout">
                <button type="submit" class="btn btn-mini" style="margin-left: 13px; padding: 0.1em 0.7em">Log out</button>
            </form></li>';
    
}

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

	echo'	</div> <!-- /container -->
	<div class="container footer">
		<div class="row">
		  <div class="span3"><strong>Contact</strong><br />
		  This site is part of the HumanitarianResponse network. Write to 
		  <a href="mailto:info@humanitarianresponse.info">info@humanitarianresponse.info</a> for more information.</div>
		  <div class="span3"><strong>Links</strong><br />
		  <a href="https://sites.google.com/site/hxlproject/">HXL Project</a><br />
		  <a href="http://hxl.humanitarianresponse.info/">HXL Standard</a></div>
		  <div class="span3"><strong>Follow HXL</strong><br />
		  <span class="label label-warning">TBD</span></div>
		  <div class="span3"><strong>Legal</strong><br />
		  &copy; 2012 UNOCHA</div>
		</div>
	</div>
	
    <script src="js/jquery.js"></script> 
    <script src="js/json2.js"></script> 
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
function loadHXLPreviewModal(){
	echo '
	<div id="hxlPreview" class="modal hide fade">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal">×</button>
              <h3>HXL Preview</h3>              
            </div>
            <div class="modal-body">
            
            <div class="tabbable" style="margin-bottom: 18px;">
                    <ul class="nav nav-tabs">
                      <!-- <li><a href="#previewtaboverview" data-toggle="tab">Overview</a></li> -->
                      <li class="active"><a href="#previewtabtable" data-toggle="tab">Table</a></li>
                      <li><a href="#previewtabturtle" data-toggle="tab">Turtle</a></li>
                    </ul>
                    <div class="tab-content" style="padding-bottom: 9px; border-bottom: 1px solid #ddd;">
                      <!-- <div class="tab-pane" id="previewtaboverview">
                        <p><span class="label label-warning">TBD</span></p>
                      </div> -->
                      <div class="tab-pane active" id="previewtabtable">
                        <p><span class="label label-warning">Please map some properties first, this preview will then show what you have done so far.</span></p>
                      </div>
                      <div class="tab-pane" id="previewtabturtle">
                      	<p>Preview in <a href="http://en.wikipedia.org/wiki/Turtle_(syntax)" target="_blank">Turtle syntax</a>:</p>
                        <code>
                        	<pre id="nakedturtle"></pre>
                        </code>
                      </div>
                    </div>
                  </div>            
            </div>
            <div class="modal-footer">
              <a href="#" class="btn" data-dismiss="modal">Close</a>
              </div>
          </div>
	';
}


// Generates the empty mapping modal (see http://twitter.github.com/bootstrap/javascript.html#modals ) that 
// we'll be using to for the user interaction during the mapping process
function loadMappingModal(){
	echo '
	<div id="mappingModal" class="modal hide fade">
            <div class="modal-header">
            <h3>Here goes the mapping magic...</h3>
            </div>

            <div class="modal-body"></div>

            <div class="modal-footer"></div>
    </div>     
	';
}


// ------- some convenience functions

function shorten($uri, $prefix){
	$parts = explode("#", $uri);
	if ($prefix == ''){
		return $parts[1];
	}else{
		return $prefix.":".$parts[1];
	}
}

function showError($msg){
	echo '<div class="container"><div class="alert alert-error"><h2>Oops.</h2>'.$msg.'</div>';
}

?>