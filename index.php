<?php 

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


include_once('functions.php');
getHead(); 

include('sparqlQueries.php');

$user_name = "John Doe";
$user_uri = "http://hxl.humanitarianresponse.info/data/persons/unhcr/john_doe";
$user_organisation = "UNHCR";
$user_organisation_uri = "http://hxl.humanitarianresponse.info/data/orgs/unhcr";

?>

<div class="container">
    <div class="hero-unit">	    
        <a href="https://sites.google.com/site/hxlproject/"><img src="img/hxl-logo-s.png" align="right" /></a>
        <h1>HXLator</h1>
        <p style="margin-top:  1.5em; margin-bottom:  1.5em">A simple online tool to convert <em>any</em> spreadsheet into the Humanitarian eXchange Langue (HXL). Take a look at our <a href="guide.php">quick start guide</a> or start by uploading your spreadsheet here:</p>	    

        <p>Welcome <?php echo $user_name; ?> from <?php echo $user_organisation; ?>, you are logged in.</p>
        <p>Please, specify below the emergency you are working on, select a report category and eventually upload the file.</p>
        
        <form class="alert" enctype="multipart/form-data" action="hxlate.php#" method="POST">

            <input type="hidden" name="user_name" value="<?php echo $user_name; ?>">
            <input type="hidden" name="user_uri" value="<?php echo $user_uri; ?>">
            <input type="hidden" name="user_organisation" value="<?php echo $user_organisation; ?>">
            <input type="hidden" name="user_organisation_uri" value="<?php echo $user_organisation_uri; ?>">

            <label for="tags">Emergency: </label>
            <input type="text" id="tags" name="emergency" /> Start to type and select from the emergencies list.

            <label >Report category: </label>
            <select name="report_category" >
                <option value="http://hxl.humanitarianresponse.info/data/reportcategories/cluster1">Cluster 1</option>
                <option value="http://hxl.humanitarianresponse.info/data/reportcategories/cluster2">Cluster 2</option>
                <option value="http://hxl.humanitarianresponse.info/data/reportcategories/humanitarian profile">Humanitarian profile</option>
                <option value="http://hxl.humanitarianresponse.info/data/reportcategories/security">Security</option>
            </select> 

            Upload the spreadsheet here:<br />
            <input type="hidden" name="MAX_FILE_SIZE" value="5242880"><!-- 5MB -->
            <input name="userfile" type="file"><br />
            <br />
            <button type="submit" class="btn">HXLate File</button>

        </form>  
    </div>	   
</div> <!-- /container -->
<script>document.getElementById('tags').focus()</script>


<?php 
	$customJS = emergencyQuery();
	getFoot(array('jquery-ui-1.8.21.custom.min.js'), $customJS ); 


?>