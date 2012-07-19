<?php 

include_once('functions.php');

/*
 * Send a sparql query to retreive the emergencies names and hide the result
 * in a span as a * splitable string.
 */
function emergencyQuery()
{
    $emergencies = sparqlQuery('SELECT DISTINCT ?uri ?label WHERE {
        GRAPH <http://hxl.humanitarianresponse.info/data/reference/fts-emergencies-2012> {
            ?uri hxl:commonTitle ?label .
        }
    }');
    
    $label = "label";
    $uri   = "uri";

	// we'll return the whole JS code here - if we only return the array of emergencies, PHP renders the array as a table instead of simply passing on the string :/
	$elist = '
	
	/*
	 * Provides the autocomplete function with an array of emergency names itself
	 * provided by the emergency query php function.
	 */
	
	$("#tags").autocomplete({
	    source:[ ';
	     
    foreach($emergencies as $emergency){
        $elist .= ' { label: "'.$emergency->$label.'", value: "'.$emergency->$uri.'"}, ' ;                 
    }
    
    // remove trailing comma:
	//	$elist = substr($emergencies, 0, -2);    
             
    $elist .= ' {} ]
    	});
    ';
    
    return $elist;
}

?>
