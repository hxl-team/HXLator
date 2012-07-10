<?php 
header("Access-Control-Allow-Origin: *"); 

include_once('functions.php');

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
	
	echo '<div class="container">
			<div class="row">
			<div class="span12">
			<h1><img src="img/loader.gif" id="loader" align="right" style="display: none" />HXLating <em>'.$_FILES["userfile"]["name"].'</em></h1>
			</div>
			</div>
			</div>
			<div class="shortguide container">
			<div class="step1">
			<p class="lead">What is the data in this spreadsheet <em>primarily</em> about? Hover for explanations: </p>
						
			<div class="row">';
		
	echo getClassPills();
		
	echo '
		</div>
		</div>
		<!--<div class="span4"><div class="well" id="mappings" style="display: none"><h2 style="margin-bottom: 15px">Mappings to HXL</h2></div></div>-->
		</div> <!-- shortguide -->
		<div class="container">
		';
	
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
getFoot(array("bootstrap-tooltip.js", "bootstrap-popover.js", "bootstrap-dropdown.js",  "hxlhistory.js", "hxlator.js", ));

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






// function to show horziontally stacked pills of the HXL class hierarchy that fold out to the right, 
// revealing a classes subclasses when the corresponding pill is clicked
//function getClassPills($hxlClasses = null, $superclass = null, $pills = ""){
//	
//	if($hxlClasses == null){	
//		$hxlClasses = sparqlQuery('SELECT  ?subclass ?label ?description ?super ?top (COUNT(?subsub) as ?subsubCount) WHERE {  
//	  		?top hxl:topLevelConcept "true"^^xsd:boolean .
//			?subclass rdfs:subClassOf* ?top ;                          
//	        	rdfs:subClassOf ?super ;                                    
//				skos:prefLabel ?label  ;     
//		  		rdfs:comment ?description .
//	  		OPTIONAL { ?subsub rdfs:subClassOf ?subclass }
//		} GROUP BY ?subclass ?label ?description ?super ?top ORDER BY ?label ');
//	}
//	
	// start with the top-level pills:
//	$thisPill = "<ul class='nav nav-pills nav-stacked hxl-pills'>
//		  ";
//	
//	$label = "label";
//	$class = "subclass";
//	$description = "description";	  	
//	$count = "subsubCount";	  	
//	$super = "super";	  	
//	$top = "top";
//	
//	foreach($hxlClasses as $row){	  	
//			
//	  	if($superclass == null && $row->$class == $row->$top){
//		if(($superclass == null && $row->$class == $row->$top)  // toplevel classes
//			|| $row->$super == $superclass){					// subclasses of $superclass
//						
//		  	if ($row->$count != "0"){ 	// if there are any subclasses:
	//	  		print '	
	//	  		<div class="btn-group"><button class="btn hxlclass" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'<p style=\'margin-top:10px\'><span class=\'label label-info\'>Click to show more specific subclasses</span>" classuri="'.shorten($row->$class).'">'.multiply($row->$label).' <b class="icon-info-sign"></b></button>
	//	  		    <button class="btn dropdown-toggle" data-toggle="dropdown">
	//	  		        <span class="caret"></span>
	//	  		    </button>
	//	  			<ul class="dropdown-menu">
	//	  		';
	//	  		
	//	  		foreach ($subclasses as $subclass) {
	//					print '     <li><a class="hxlclass" href="#" rel="popover" title="'.$subclass->$label.'" data-content="'.$subclass->$description.'" classuri="'.shorten($row->$class).'">'.multiply($subclass->$label).' <b class="icon-info-sign"></b></a></li>
	//	   				';		  			
	//	  		}
	//	  		
	//	  		print '</ul>
	//	  		</div>
	//	  		</div><!-- step1 -->
	//	  		';
//				$thisPill .= '<li><a href="#" class="hxlclass" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.' <br /><small><strong>Click to view '.$row->$count.' subclasses.<strong></small>" classuri="'.shorten($row->$class).'">'.multiply($row->$label).' <span class="badge pull-right">'.$row->$count.'</span></a></li>';
//								
//		  	} else {	  	// if there are no subclasses:	  		  
//	
//				$thisPill .= '<li><a href="#" class="hxlclass selectable" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'" classuri="'.shorten($row->$class).'">'.multiply($row->$label).' </a></li>';
//	
	//	  		print '	<div class="btn-group"><button class="btn hxlclass" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'" classuri="'.shorten($row->$class).'">'.multiply($row->$label).' <b class="icon-info-sign"></b></button></div>
	//	  		';
//		  	}	
//		  	}	  		
//	  }  
//	  	    	    
//	$thisPill .= "</ul>";	
//	
//	$pills .= $thisPill;
//	
	// recursion
//	foreach($hxlClasses as $row){
//		if ((($row->$class == $row->$top)  // toplevel classes
//			|| $row->$super == $superclass) && $row->$count != "0"){  					
//			error_log(" ---- Going into recursion for ".$row->$class);
//			$pills = getClassPills($hxlClasses, $row->$class, $pills);	
//		}else{
//			error_log("NO recursion for ".$row->$class);
//		}		
//	}
//	
//	return $pills;	
//}


function getClassPills($superclass = null){
	
	$recursionClasses = array();
	
	if($superclass == null){
		$hxlClasses = sparqlQuery('SELECT  ?class ?label ?description (COUNT(?subsub) as ?subsubCount) WHERE {  
	  		?class  hxl:topLevelConcept "true"^^xsd:boolean ;
				skos:prefLabel ?label  ;     
			  	rdfs:comment ?description .
		  	OPTIONAL { ?subsub rdfs:subClassOf ?class }
		} GROUP BY ?class ?label ?description ORDER BY ?label');
		
		$pills = '<div class="span3"><ul class="nav nav-pills nav-stacked hxl-pills">
		';
		
	} else {
		$hxlClasses = sparqlQuery('SELECT  ?class ?label ?description (COUNT(?subsub) as ?subsubCount) WHERE {  
		  	?class  rdfs:subClassOf <'.$superclass.'> ;
				skos:prefLabel ?label  ;     
			  	rdfs:comment ?description .
		  	OPTIONAL { ?subsub rdfs:subClassOf ?class }
		} GROUP BY ?class ?label ?description ORDER BY ?label ');
		
		$pills = '<div class="span3 hxl-hidden" subclassesof="'.shorten($superclass).'"><ul class="nav nav-pills nav-stacked hxl-pills">
		';
					
	}
	
	
	$label = "label";
	$class = "class";
	$description = "description";	  	
	$count = "subsubCount";	  			
				
	
	
	foreach($hxlClasses as $hxlClass){	  	
		if($hxlClass->$count != "0") { 
			$pills .= '<li><a href="#" class="hxlclass hxlclass-expandable" rel="popover" title="'.$hxlClass->$label.'" data-content="'.$hxlClass->$description.' <br /><small><strong>Click to view '.$hxlClass->$count.' subclasses.<strong></small>" classuri="'.shorten($hxlClass->$class).'">'.multiply($hxlClass->$label).'<span class="badge pull-right">'.$hxlClass->$count.'</span>'; 
			// we're gonna show subclasses for this one:
			$recursionClasses[] = $hxlClass->$class;
		}else{
			$pills .= '<li><a href="#" class="hxlclass hxlclass-selectable" rel="popover" title="'.$hxlClass->$label.'" data-content="'.$hxlClass->$description.'" classuri="'.shorten($hxlClass->$class).'">'.multiply($hxlClass->$label);
		}
		
		$pills .= '</a></li>';
	}
	
	if($superclass != null){
		$pills .= '<li><a href="#" class="hxlclass hxlclass-selectable" rel="popover" title="Mix of different '.multiply($superclass).'" data-content="Select this option if you have serveral of those classes in your data." classuri="'.shorten($superclass).'">It is a <em>mix</em> of those.</a></li>';
	}
	
	$pills .= '</ul></div>
	';
	
	foreach ($recursionClasses as $recClass) {
		$pills .= getClassPills($recClass);
	}
	
	return $pills;
}






// ------- some convenience functions

function shorten($uri){
	$parts = explode("#", $uri);
	return "hxl:".$parts[1];
}


function showError($msg){
	echo '<div class="container"><div class="alert alert-error"><h2>Oops.</h2>'.$msg.'</div>';
}

?>
