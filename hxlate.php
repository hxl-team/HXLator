<?php 
header("Access-Control-Allow-Origin: *"); 

include_once('functions.php');

$hxlTopConcepts = sparqlQuery('SELECT * WHERE {  
  GRAPH <http://hxl.humanitarianresponse.info/data/vocabulary/latest/> {     
    ?class hxl:topLevelConcept "true"^^xsd:boolean ;        
           skos:prefLabel ?label  ;     
           rdfs:comment ?description .
  }
} ORDER BY ?label');

getHead("index.php"); 


//  5MB maximum file size 
$MAXIMUM_FILESIZE = 5 * 1024 * 1024; 
//  Valid file extensions: 
$rEFileTypes = "/^\.(csv|txt|xls|xlsx|ods|fods){1}$/i"; 
$dir_base = getcwd().'/uploads/';
$uploadfile = ""; // we'll set this later

$isFile = is_uploaded_file($_FILES['userfile']['tmp_name']); 
$isMove = false;

if ($isFile) {//  sanatize file name 
    //     - remove extra spaces/convert to _, 
    //     - remove non 0-9a-Z._- characters, 
    //     - remove leading/trailing spaces 
    //  check if under 5MB, 
    //  check file extension for legal file types 
    $safe_filename = preg_replace( 
                     array("/\s+/", "/[^-\.\w]+/"), 
                     array("_", ""), 
                     trim($_FILES['userfile']['name'])); 
                     
    $uploadfile = $dir_base.$safe_filename; 
    
    if ($_FILES['userfile']['size'] <= $MAXIMUM_FILESIZE){ 
        if(preg_match($rEFileTypes, strrchr($safe_filename, '.'))) {
        	$isMove = move_uploaded_file ( $_FILES['userfile']['tmp_name'], $uploadfile);
        } else {
        	showError("<strong>".$_FILES['userfile']['name']."</strong> does not seem to be a spreadsheet.");
        }	
    } else {
    	showError("<strong>".$_FILES['userfile']['name']."</strong> is too large. The limit for uploaded files is <strong>5MB</strong>.");
    }
    
} else {

	showError("There is something wrong with <strong>".$_FILES['userfile']['name']."</strong>.");

}



// get going if the file upload has worked:

if($isMove === true) {

	/** Include path **/
	set_include_path(get_include_path() . PATH_SEPARATOR . './Classes/');
	
	/** PHPExcel_IOFactory */
	include 'PHPExcel/IOFactory.php';
	
	
	// using IOFactory to identify the format
	$workbook = load($uploadfile);
	
	echo "<div class='container'>
		<div class='row'>
		<div class='span12'>
		<h1><img src='img/loader.gif' id='loader' align='right' style='display: none' />HXLating <em>".$_FILES['userfile']['name']."</em></h1>
		</div>
		</div>
		<div class='row'><div class='shortguide span8'>
		<div class='step1'>
		<p class='lead'>Please start by telling us what the data in this spreadsheet is <em>primarily</em> about: </p>
		<div class='btn-toolbar'>";
	  foreach($hxlTopConcepts as $row){
	  	$label = "label";
	  	$class = "class";
	  	$description = "description";	  		  	
	  	
	  	$subclasses = sparqlQuery('prefix skos: <http://www.w3.org/2004/02/skos/core#> 
	  	prefix hxl:   <http://hxl.humanitarianresponse.info/ns-2012-06-14/#> 
	  	prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
	  	
	  	SELECT DISTINCT * WHERE {  
	  	  GRAPH <http://hxl.humanitarianresponse.info/data/vocabulary/latest/> {     
	  	    ?subclass rdfs:subClassOf+ <'.$row->$class.'> ;        
	  	           skos:prefLabel ?label  ;     
	  	           rdfs:comment ?description .
	  	  }
	  	} ORDER BY ?label');
	  	
	  	// create a dropdown menu if that class has subclasses:
	  	if ($subclasses->numRows() > 0){
	  		print '	
	  		<div class="btn-group"><button class="btn hxlclass" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'<p style=\'margin-top:10px\'><span class=\'label label-info\'>Click to show more specific subclasses</span>" classuri="'.shorten($row->$class).'">'.multiply($row->$label).' <b class="icon-info-sign"></b></button>
	  		    <button class="btn dropdown-toggle" data-toggle="dropdown">
	  		        <span class="caret"></span>
	  		    </button>
	  			<ul class="dropdown-menu">
	  		';
	  		
	  		foreach ($subclasses as $subclass) {
					print '     <li><a class="hxlclass" href="#" rel="popover" title="'.$subclass->$label.'" data-content="'.$subclass->$description.'" classuri="'.shorten($row->$class).'">'.multiply($subclass->$label).' <b class="icon-info-sign"></b></a></li>
	   				';		  			
	  		}
	  		
	  		print '</ul>
	  		</div>
	  		</div><!-- step1 -->
	  		';
	  		
	  	} else {	  	// if there are no subclasses:	  		  
	  		print '	<div class="btn-group"><button class="btn hxlclass" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'" classuri="'.shorten($row->$class).'">'.multiply($row->$label).' <b class="icon-info-sign"></b></button></div>
	  		';
	  	}		  		
	  }  
	    	    
	echo "
		</div>		
		</div>
		<div class='span4'><div class='well' id='mappings' style='display: none'><h2 style='margin-bottom: 15px'>Mappings to HXL</h2></div></div>
		</div> <!-- row -->
		";
	
	// Let's show the spreadsheet"
	// iterate once for the tabs (i.e., one tab per sheet in the workbook)
	echo '<div class="tabbable" style="margin-bottom: 18px;">
	          <ul class="nav nav-tabs">';
	
	$tabno = 0; 
	foreach ($workbook->getWorksheetIterator() as $worksheet) {    
		if($tabno === 0){  // make the first tab active
	   		echo '   <li class="active"><a href="#tab0" data-toggle="tab">'.$worksheet->getTitle().'</a></li>';
	    }else{
		   	echo '   <li><a href="#tab'.$tabno.'" data-toggle="tab">'.$worksheet->getTitle().'</a></li>';		   
	    }
	    $tabno++;
	}            
			            
	    echo'      </ul>
	          <div class="tab-content" style="padding-bottom: 9px; border-bottom: 1px solid #ddd;">';
	            
	
	$tabno = 0;
	// iterate through all sheets in this file
	foreach ($workbook->getWorksheetIterator() as $worksheet) {
		//$sheetData = $workbook->getActiveSheet()->toArray(null,true,true,true); 
		$sheetData = $worksheet->toArray(null,true,true,true); 
	
		if ($tabno === 0){
			echo '<div class="tab-pane active" id="tab0">';
		}else{
			echo '<div class="tab-pane" id="tab'.$tabno.'">';
		}
		echo "
		
			<table class='table table-striped table-bordered table-condensed'>";
	
		makeTableHead($sheetData);				
		makeTableBody($sheetData);		
	
		echo"
			  </table>
			</div>";
			
		$tabno++;
	}
	
	echo   ' </div>
		</div>
	</div>';
	
	// we're done - delete the uploaded file
	unlink($uploadfile);
	
	
} 

// returns the plural for $class, fixing words ending on "-y" to "-ies"
function multiply($class){
	if (substr($class, -1) === "y") {
		return substr($class, 0, -1).'ies';
	} else {
		return $class.'s';
	}
}


// some functions from IOFactory.php, slightly adopted for our use case:
function load($pFilename) {
	$reader = createReaderForFile($pFilename);
	return $reader->load($pFilename);
}

function createReaderForFile($pFilename) {

		// First, lucky guess by inspecting file extension
		$pathinfo = pathinfo($pFilename);

		if (isset($pathinfo['extension'])) {
			switch (strtolower($pathinfo['extension'])) {
				case 'xlsx':
					$extensionType = 'Excel2007';
					break;
				case 'xls':
				case 'xlsm':
					$extensionType = 'Excel5';
					break;
				case 'ods':
					$extensionType = 'OOCalc';
					break;
				case 'slk':
					$extensionType = 'SYLK';
					break;
				case 'xml':
					$extensionType = 'Excel2003XML';
					break;
				case 'gnumeric':
					$extensionType = 'Gnumeric';
					break;
				case 'htm':
				case 'html':
					$extensionType = 'HTML';
					break;
				case 'csv':
					// Do nothing
					// We must not try to use CSV reader since it loads
					// all files including Excel files etc.
					break;
				default:
					showError('This file does not seem to be a spreadsheet.</p><p>If you are sure it is, there is something wrong with the HXLator; in that case, please <a href="contact.php">get in touch with us</a>, so we can fix it.'); 
					break;
			}

			$reader = PHPExcel_IOFactory::createReader($extensionType);
			// Let's see if we are lucky
			if (isset($reader) && $reader->canRead($pFilename)) {
				//$reader->setReadDataOnly(true); // we only need the data
				return $reader;
			}

		}

		// If we reach here then "lucky guess" didn't give any result
		// Try walking through all the options in PHPExcel_IOFactory::$_autoResolveClasses
		foreach (PHPExcel_IOFactory::$_autoResolveClasses as $autoResolveClass) {
			//	Ignore our original guess, we know that won't work
		    if ($reader !== $extensionType) {
				$reader = PHPExcel_IOFactory::createReader($autoResolveClass);
				if ($reader->canRead($pFilename)) {
					//$reader->setReadDataOnly(true); // we only need the data
					return $reader;
				}
			}
		}

    	print '<p>This file does not seem to be a spreadsheet.</p>'; // TODO make this nicer
	}	//	function createReaderForFile()



// load the footer, along with the extra JS required for this page
getFoot(array("bootstrap-tooltip.js", "bootstrap-popover.js", "bootstrap-dropdown.js", "hxlator.js", ));

function makeTableHead($sheetData){
	echo"
	<thead>";
	
		foreach ($sheetData as $rownumber => $rowcontents) {
			if($rownumber == 1){
				echo "
						<tr class='hxlatorrow'>";
					echo "
							<th class='hxlatorcell'>".$rownumber."</th>";		
				foreach ($rowcontents as $cell => $cellvalue) {
					echo "
							<th class='hxlatorcell'>".$cellvalue."</th>";	
				}		
				
				echo "
						</tr>";		
			}else{
				// we're doing just the first row, then quit:
				echo "
						</head>";
				return; 
			}
		}
		
		
}

function makeTableBody($sheetData){
	echo"
	<tbody>";
	
		foreach ($sheetData as $rownumber => $rowcontents) {
			if($rownumber != 1){
				echo "
						<tr class='hxlatorrow'>";
					echo "
							<th class='hxlatorcell'>".$rownumber."</th>";		
				foreach ($rowcontents as $cell => $cellvalue) {
					echo "
							<td class='hxlatorcell'>".$cellvalue."</td>";	
				}		
				
				echo "
						</tr>";		
			}
		}
		
		echo "
				</tbody>";
}

function shorten($uri){
	$parts = explode("#", $uri);
	return "hxl:".$parts[1];
}

function showError($msg){
	echo '<div class="container"><div class="alert alert-error"><h2>Oops.</h2>'.$msg.'</div>';
}
?>
