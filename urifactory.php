<?php 

// this script can be used via AJAX to generate URIs using the makeURI function in functions.php

include_once('functions.php');

echo makeURI($_POST["type"], $_POST["properties"]);

?>