<?php 

include_once('functions.php');

$domainprops = sparqlQuery('prefix skos: <http://www.w3.org/2004/02/skos/core#> 
prefix hxl:   <http://hxl.humanitarianresponse.info/ns-2012-06-14/#> 
prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 

SELECT * WHERE {  
  GRAPH <http://hxl.humanitarianresponse.info/data/vocabulary/latest/> {     
    '.$_GET['classuri'].' rdfs:subClassOf* ?class .           
    ?prop rdfs:domain ?class ;
          rdfs:range ?range ;
          rdfs:comment ?description ;
          skos:prefLabel ?proplabel .
  }
} ORDER BY ?proplabel');


$rangeprops = sparqlQuery('prefix skos: <http://www.w3.org/2004/02/skos/core#> 
prefix hxl:   <http://hxl.humanitarianresponse.info/ns-2012-06-14/#> 
prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 

SELECT * WHERE {  
  GRAPH <http://hxl.humanitarianresponse.info/data/vocabulary/latest/> {     
    ?class rdfs:subClassOf* '.$_GET['classuri'].' .
    ?prop rdfs:domain ?domain ;
          rdfs:range ?class ;
          rdfs:comment ?description ;
          skos:prefLabel ?proplabel .
  }
} ORDER BY ?proplabel');


	
	echo '<div class="step4"><ul class="nav nav-pills properties">
	  ';
		
	  $label = "proplabel";
	  $class = "class";
	  $description = "description";	  		  	
	  $range = "range"; // TODO
	  
	  foreach($domainprops as $row){
	  	
	  	print '	<li><a class="btn hxlclass" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'">'.$row->$label.' <b class="icon-info-sign"></b></a></li>
	  		';
	  			  		
	  }
	  
	  foreach($rangeprops as $row){
	  	
	  	print '	<li><a class="btn hxlclass" href="#" rel="popover" title="'.$row->$label.'" data-content="'.$row->$description.'">'.$row->$label.' <b class="icon-info-sign"></b></a></li>
	  		';
	  			  		
	  }  
	    	    
	echo "</ul>	
		</div>	
		";
?>
