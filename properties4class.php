<?php 
	// returns the properties for the class passed via $_GET['classuri'] as HTML
	// to be called via AJAX
	
	include_once('functions.php');
	
	$props = sparqlQuery('SELECT DISTINCT ?domain ?range ?proplabel ?description ?prop WHERE {  
	  GRAPH <http://hxl.humanitarianresponse.info/data/vocabulary/latest/> {     
	    { '.$_GET['classuri'].' rdfs:subClassOf* ?range  .
	      ?prop rdfs:domain ?domain ;
	            rdfs:range ?range ;
	            rdfs:comment ?description ;
		    skos:prefLabel ?proplabel . }
	    UNION {
	      '.$_GET['classuri'].' rdfs:subClassOf* ?domain  .
	      ?prop rdfs:domain ?domain ;
	            rdfs:range ?range ;
		    rdfs:comment ?description ;
	            skos:prefLabel ?proplabel .
	    }
	  }
	} ORDER BY ?proplabel');
	
	echo '<div class="step4"><ul class="nav nav-pills properties">
	  ';
		
	  $label = "proplabel";
	  $class = "class";
	  $description = "description";	  		  	
	  $range = "range"; 
	  
  	  foreach($props as $row){
	  	
	  	print '	<li><a class="btn hxlclass hxlprop disabled" data-hxl-uri="'.$row->$class.'" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'">'.$row->$label.'</a></li>
	  		';
	  			  		
	  }
  	  	    	    
	echo "</ul>	
		</div>	
		";
?>
