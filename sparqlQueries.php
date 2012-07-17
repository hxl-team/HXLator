<?php 

include_once('functions.php');

/*
 * Send a sparqk query to retreive the emergencies names and hide the result
 * in a span as a * splitable string.
 */
function emmergencyQuery()
{
    $emergencies = sparqlQuery('SELECT ?label WHERE {
        GRAPH <http://hxl.humanitarianresponse.info/data/reference/fts-emergencies-2012> {
            ?uri <http://hxl.humanitarianresponse.info/ns/#commonTitle> ?label .
        }
    }');

    echo '<span id="emergency_list" style="display:none;" >
    ';
	
    $label = "label";
    $i = 0;
    foreach($emergencies as $emergency){
        $i++;

        print $emergency->$label;

        if ($i != count($emergencies)) {                
            echo '*';
        }		  		
    }
             
    echo "</span>";
}

?>
