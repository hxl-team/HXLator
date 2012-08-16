<?php 
	// returns the properties for the class passed via $_GET['classuri'] as HTML
	// to be called via AJAX
	
	include_once('functions.php');
	
	$props = sparqlQuery('SELECT DISTINCT ?proplabel ?prop ?type ?description ?domain ?range ?rangename WHERE {
   '.$_GET['classuri'].' rdfs:subClassOf* ?domain  .
   ?prop rdfs:domain ?domain ;
        a ?type ; 
	rdfs:range ?range ;
	rdfs:comment ?description ;
	skos:prefLabel ?proplabel .	
	OPTIONAL { ?range skos:prefLabel ?rangename . }
MINUS { ?subprop rdfs:subPropertyOf ?prop }	
FILTER ( regex(str(?type),"http://www.w3.org/2002/07/owl") )
} ORDER BY ?proplabel');
	
	echo '<div class="step4"><ul class="nav nav-pills properties">
	  ';
		
	  $label = "proplabel";
	  $prop = "prop";
	  $description = "description";	  		  	
	  $range = "range"; 
	  $type = "type";
	  $rangename = "rangename";
	  
	  foreach($props as $row){
	  	
	  	if($row->$rangename){
	  		$rangelable = $row->$rangename;
	  	}else{
	  	 	$rangelable = shorten($row->$range, '');
	  	}	
	  	
	  	print '	<li><a class="btn hxlclass hxlprop disabled" data-hxl-uri="'.$row->$prop.'" data-hxl-propertytype="'.$row->$type.'" data-hxl-range="'.$row->$range.'" data-hxl-range-name="'.$rangelable.'" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'">'.$row->$label.'</a></li>
	  		';
	  			  		
	  }
  	  	    	    
	echo "</ul>	
		</div>	
		";
?>
