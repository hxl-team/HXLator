<?php 
	// returns the properties for the class passed via $_GET['classuri'] as HTML
	// to be called via AJAX
	
	include_once('functions.php');
	
	$props = sparqlQuery('

SELECT DISTINCT ?proplabel ?prop ?type ?description ?domain ?range ?rangename WHERE {
   ?class rdfs:subClassOf* ?domain .
   ?prop rdfs:domain ?domain ;
         a ?type ; 
	     rdfs:range ?range ;
	     rdfs:comment ?description ;
	     skos:prefLabel ?proplabel .	
	
	OPTIONAL { ?range skos:prefLabel ?rangename . }

FILTER NOT EXISTS {
    ?sub rdfs:subPropertyOf ?prop.
    ?sub rdfs:domain ?d.
    ?class rdfs:subClassOf* ?d .
}
FILTER (?class = '.$_GET['classuri'].')
FILTER ( regex(str(?type),"http://www.w3.org/2002/07/owl") )

} ORDER BY ?proplabel', 'http://hxl.humanitarianresponse.info/sparql');

	echo '<div class="step4"><ul class="nav nav-pills properties">
	  ';
		
	  $label = "proplabel";
	  $prop = "prop";
	  $description = "description";	  		  	
	  $range = "range"; 
	  $type = "type";
	  $rangename = "rangename";
	  
	  foreach($props as $row){
	  
	    // shorten properties:
	    $property = shorten($row->$prop, 'hxl');
	  	
	  	if(isset($row->$rangename)){
	  		$rangelabel = $row->$rangename;
	  	}else{
	  	 	$rangelabel = shorten($row->$range, '');
	  	}	
	  	
	  	print '	<li><a class="btn hxlclass hxlprop disabled" data-hxl-uri="'.$property.'" data-hxl-propertytype="'.$row->$type.'" data-hxl-range="'.$row->$range.'" data-hxl-range-name="'.$rangelabel.'" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'">'.$row->$label.'</a></li>
	  		';
	  			  		
	  }
  	  	    	    
	echo "</ul>	
		</div>	
		";
?>
