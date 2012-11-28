<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');

include_once('functions.php');

mkdir(getcwd().'/backups/'.date('Y-m-d'));
cleanUp();
die();

// configure the script:
$graphstore = 'http://hxl.humanitarianresponse.info/graphstore';  // Graphstore Endpoint
$credentials = '../../store.txt' ; // file with username/password

// load password for triple store from file:
$login = file_get_contents($credentials);
$graphs = sparqlQuery('SELECT DISTINCT ?graph WHERE { GRAPH ?graph { ?a ?b ?c } }');	

// store a restore shell script outside of the server space. 
// Executing this will restore the last backup on the endpoint
$restore = fopen(getcwd().'../../restore.sh', "a");

foreach ($graphs as $g) {
    $gr = 'graph';
    $graph = $g->$gr;
    
    $filepath = '/backups/'.date('Y-m-d').'/'.str_replace('/', '_SLASH_', $graph).'.rdf';

    $fh = fopen(getcwd().$filepath,'w');
    $get = curl_init();
     
    curl_setopt($get, CURLOPT_URL, $graphstore.'?graph='.$graph);
    curl_setopt($get, CURLOPT_USERPWD, $login);
    curl_setopt($get, CURLOPT_FILE, $fh);
    
    curl_exec($get);

    curl_close($get);
    fclose($fh);    

    fwrite($restore, 'curl -i --user '.$login.' --data-urlencode "update=LOAD <http://localhost/HXLator'.$filepath.'> INTO GRAPH <'.$graph.'>" http://hxl.humanitarianresponse.info/update; \n');

    echo 'Named Graph <b>'.$graph.'</b> backed up.<br />';
}

fclose($restore);

// deletes backups older than a week
function cleanUp(){
    
    $backups = array();

    if ($handle = opendir(getcwd().'/backups')) {
        
        /* This is the correct way to loop over the directory. */
        while (false !== ($entry = readdir($handle))) {
            if(strpos($entry, '.') !== 0){
                $backups[] = $entry;    
            }            
        }
        
        closedir($handle);
    }

    arsort($backups);

    $i = 0;
    foreach ($backups as $key => $value) {
        // only keep the last 7 backups
        if($i++ >= 7){
            rrmdir(getcwd().'/backups/'.$value);
        }        
    }
}

// recursively delete a directory and all of its contents
function rrmdir($dir) {
  if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
  }
}

?>