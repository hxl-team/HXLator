<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');

include_once('functions.php');

// configure the script:

$graphstore = 'http://hxl.humanitarianresponse.info/graphstore';  // Graphstore Endpoint
$credentials = '../../store.txt' ; // file with username/password
	


// load password for triple store from file:
$login = file_get_contents($credentials);
$graphs = sparqlQuery('SELECT DISTINCT ?graph WHERE { GRAPH ?graph { ?a ?b ?c } }');	

foreach ($graphs as $g) {
    $gr = 'graph';
    $graph = $g->$gr;
    

    $fh = fopen(getcwd().'/backups/'.str_replace('/', '_SLASH_', $graph).'.rdf','w');
    $get = curl_init();
     
    curl_setopt($get, CURLOPT_URL, $graphstore.'?graph='.$graph);
    curl_setopt($get, CURLOPT_USERPWD, $login);
    curl_setopt($get, CURLOPT_FILE, $fh);
    
    curl_exec($get);

    curl_close($get);
    fclose($fh);    

    echo 'Named Graph <b>'.$graph.'</b> backed up.<br />';
}


?>