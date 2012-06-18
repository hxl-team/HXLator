<?php 

set_include_path('Classes/');

require_once "EasyRdf.php";
require_once "html_tag_helpers.php";
    
// fires the $query against our SPARQL endpoint and returns a EasyRDF Sparql_Result object 
// (see http://www.aelius.com/njh/easyrdf/docs/EasyRdf/EasyRdf_Sparql_Result.html)
// this is basically an ArrayIterator with some extras
function sparqlQuery($query){

  $sparql = new EasyRdf_Sparql_Client('http://hxl.humanitarianresponse.info/sparql');
  
  try {
      $results = $sparql->query($query);      
      return $results;
  } catch (Exception $e) {
      return "<div class='error'>".$e->getMessage()."</div>\n";
  }
}

// generates the head for all pages, including highlighting of the activr page in the nav bar
function getHead($activePage = "hxlator"){  // TODO handle active page! ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>OCHA HXLator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/hxlator2.css" rel="stylesheet">
    <link href="css/bootstrap-responsive.css" rel="stylesheet">

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    
    
    <link rel="shortcut icon" href="img/ochaonline_theme_favicon.ico">
  </head>

  <body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="index.php">HXLator</a>
          <div class="nav-collapse">
            <ul class="nav">
              <li class="active"><a href="index.php">HXLate</a></li>
              <li><a href="guide.php">Quick Start Guide</a></li>
			  <li><a href="faq.php">FAQ</a></li>
              <li><a href="contact.php">Contact</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>    

<?php } 


// creates the footer for the page, including the JS to load
// $extraJS can contain any extra javascript that will be executed after loading all libs
function getFoot($extraJS = null){ ?>

    <script src="js/jquery.js"></script>
<!--    <script src="js/bootstrap-transition.js"></script>
    <script src="js/bootstrap-alert.js"></script>
    <script src="js/bootstrap-modal.js"></script>
    <script src="js/bootstrap-dropdown.js"></script>
    <script src="js/bootstrap-scrollspy.js"></script>
    <script src="js/bootstrap-tab.js"></script>
    <script src="js/bootstrap-tooltip.js"></script>
    <script src="js/bootstrap-popover.js"></script>
    <script src="js/bootstrap-button.js"></script>
    <script src="js/bootstrap-collapse.js"></script>
    <script src="js/bootstrap-carousel.js"></script>
    <script src="js/bootstrap-typeahead.js"></script> -->
    <?php if($extraJS){
    
    	foreach ($extraJS as $js) {
    		
	    	echo '
    		<script src="js/'.$js.'"></script>
    		';    
    	}
    }
    ?>
	
  </body>
</html>


<?php } ?>

