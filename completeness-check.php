<?php 

set_include_path('Classes/');

require_once 'EasyRdf.php';
require_once 'html_tag_helpers.php';

// load the data to check into a graph:
$test = new EasyRdf_Graph();
$ttlparser = new EasyRdf_Parser_Turtle();
//$ttlparser->parse($test, $_POST['check'], 'turtle', 'http://example.graph.org/hxl');
$ttlparser->parse($test, '@prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> . 
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . 
@prefix owl:  <http://www.w3.org/2002/07/owl#> . 
@prefix foaf: <http://xmlns.com/foaf/0.1/> . 
@prefix dc:   <http://purl.org/dc/terms/> . 
@prefix xsd:  <http://www.w3.org/2001/XMLSchema#> . 
@prefix skos: <http://www.w3.org/2004/02/skos/core#> . 
@prefix hxl:  <http://hxl.humanitarianresponse.info/ns/#> . 
@prefix geo:  <http://www.opengis.net/geosparql#> . 
@prefix label: <http://www.wasab.dk/morten/2004/03/label#> . 
 
<http://hxl.humanitarianresponse.info/data/datacontainers/1357850098.826579> a <http://hxl.humanitarianresponse.info/ns/#DataContainer> .
<http://hxl.humanitarianresponse.info/data/datacontainers/1357850098.826579> hxl:aboutEmergency <http://hxl.humanitarianresponse.info/data/emergencies/mali2012test> .
<http://hxl.humanitarianresponse.info/data/datacontainers/1357850098.826579> hxl:reportCateogry <http://hxl.humanitarianresponse.info/data/reportcategories/humanitarian_profile> .
<http://hxl.humanitarianresponse.info/data/datacontainers/1357850098.826579> hxl:reportedBy <http://hxl.humanitarianresponse.info/data/persons/unocha/carsten_kessler> .
<http://hxl.humanitarianresponse.info/data/datacontainers/1357850098.826579> hxl:reportedBy <http://hxl.humanitarianresponse.info/data/organisations/unocha> .
<http://hxl.humanitarianresponse.info/data/datacontainers/1357850098.826579> hxl:validOn "2013-01-08"^^xsd:date .
<http://hxl.humanitarianresponse.info/data/refugeesandasylumseekers/bfa/UNHCR-POC-80/female/ages_0-4> rdf:type hxl:RefugeesAsylumSeekers .
<http://hxl.humanitarianresponse.info/data/refugeesandasylumseekers/bfa/UNHCR-POC-80/female/ages_0-4> hxl:sexCategory <http://hxl.humanitarianresponse.info/data/sexcategories/female> .
<http://hxl.humanitarianresponse.info/data/refugeesandasylumseekers/bfa/UNHCR-POC-80/female/ages_0-4> hxl:ageGroup <http://hxl.humanitarianresponse.info/data/agegroups/unhcr/ages_0-4> .
<http://hxl.humanitarianresponse.info/data/refugeesandasylumseekers/bfa/UNHCR-POC-80/female/ages_0-4> hxl:atLocation <http://hxl.humanitarianresponse.info/data/locations/apl/bfa/UNHCR-POC-80> .
<http://hxl.humanitarianresponse.info/data/refugeesandasylumseekers/female/ages_5-11> rdf:type hxl:RefugeesAsylumSeekers .
<http://hxl.humanitarianresponse.info/data/refugeesandasylumseekers/female/ages_5-11> hxl:sexCategory <http://hxl.humanitarianresponse.info/data/sexcategories/female> .
<http://hxl.humanitarianresponse.info/data/refugeesandasylumseekers/female/ages_5-11> hxl:ageGroup <http://hxl.humanitarianresponse.info/data/agegroups/unhcr/ages_5-11> .', 
'turtle', 'http://example.graph.org/hxl');

// first find all types used in the data, so that we know what we have to take care of:
$types = array();
foreach ($test->resourcesMatching('rdf:type') as $resource) {

	echo '<p>Missing mandatory properties for '.$resource.':</p>';

	$mandatoryProps = sparqlQuery('
SELECT ?property WHERE {
  <'.$resource->get('rdf:type').'> rdfs:subClassOf* ?super.
  ?property rdfs:domain ?super ;
           owl:minCardinality "1" .
}');

	foreach ($mandatoryProps as $prop) {
		if(!$test->hasProperty($resource, $prop->property)){
			echo "<li>".$prop->property."</li>\n";
		}        
    }

}


function sparqlQuery($query){

	$prefixes = 'prefix xsd: <http://www.w3.org/2001/XMLSchema#>  
	prefix skos: <http://www.w3.org/2004/02/skos/core#> 
	prefix hxl:   <http://hxl.humanitarianresponse.info/ns/#> 
	prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
	prefix label: <http://www.wasab.dk/morten/2004/03/label#> 
	prefix owl: <http://www.w3.org/2002/07/owl#>
	
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



?>