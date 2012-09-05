<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');

if(isset($_SESSION['loggedin'])) {   // only delete a container if the user is logged in AND has the approver role:
  
    if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'approver'){
      // load password for triple store from file:
      $login = file_get_contents('../../store.txt');
      $container = $_GET['container'];
      
      // GET the data from the incubator store

      $get = curl_init();
     
      curl_setopt($get, CURLOPT_URL, 'http://hxl.humanitarianresponse.info/incubator-graphstore?graph='.$container);
      curl_setopt($get, CURLOPT_USERPWD, $login);
      curl_setopt($get, CURLOPT_RETURNTRANSFER, 1);
      
      $rdf = curl_exec($get);

      curl_close($get);

      if(!$rdf){
        echo 'Ooops. Something went wrong while getting the data from the incubator store.';
        die();
      }

      // POST to the public triple store
      $post = curl_init();
 
      curl_setopt($post, CURLOPT_URL, 'http://hxl.humanitarianresponse.info/graphstore?graph='.$container);
      curl_setopt($post, CURLOPT_POST, TRUE);
      curl_setopt($post, CURLOPT_USERPWD, $login);
      curl_setopt($post, CURLOPT_HTTPHEADER, array('Content-Type: application/rdf+xml'));
      curl_setopt($post, CURLOPT_POSTFIELDS, $rdf);
      curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
   
      $r = curl_exec($post);

      if(substr(curl_getinfo($post, CURLINFO_HTTP_CODE), 0, 1) == "4"){      
        echo 'Ooops. Something went wrong while pushing the data to the public triple store. The triple store returned HTTP code '.curl_getinfo($post, CURLINFO_HTTP_CODE). '('.$r.')';
        die();
      }

      
      curl_close($post);


      // DELETE from the incubator store via cURL
      $delete = curl_init();
     
      curl_setopt($delete, CURLOPT_URL, 'http://hxl.humanitarianresponse.info/incubator-graphstore?graph='.$container);
      curl_setopt($delete, CURLOPT_USERPWD, $login);
      curl_setopt($delete, CURLOPT_CUSTOMREQUEST, 'DELETE');
      
      if(curl_exec($delete)){
        echo 'Container <code>'.$container.'</code> has been approved and is now available on the public SPARQL endpoint. You can view its content <a href="http://sparql.carsten.io/?query=SELECT%20*%20WHERE%20%7B%0A%20%20GRAPH%20%3C'.str_replace(':', '%3A', $container).'%3E%20%7B%0A%20%20%20%20%3Fa%20%3Fb%20%3Fc%20.%0A%20%20%7D%0A%7D&endpoint=http%3A//hxl.humanitarianresponse.info/sparql" class="btn btn-info" target="_blank">here</a>. Note that it may take a while until the uploaded data is indexed; in case this link does not work immediately, please try again after a few minutes.';
      }else{
        echo 'Ooops. Something went wrong while deleting the data container from the incubator triple store.';
      }
     
      curl_close($delete);

    } else{
    echo '<span class="label label-warning">Not allowed.</span> Please log in with a user name that is authorized to approve data to proceed.'; 
  }

}else{
  echo 'You have to log in to delete data containers.';
}  

?>