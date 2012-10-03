<?php 
error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');

$user_name = "John Doe";
$user_uri = "http://hxl.humanitarianresponse.info/data/persons/unhcr/john_doe";
$user_organisation = "UNHCR";
$user_organisation_uri = "http://hxl.humanitarianresponse.info/data/orgs/unhcr";

include_once('functions.php');
getHead("index.php", $user_name, $user_organisation); 


?>

    <div class="hero-unit">	    
        <a href="https://sites.google.com/site/hxlproject/"><img src="img/hxl-logo-s.png" align="right" /></a>
        <h1>HXLator</h1>
        <p style="margin-top:  1.5em; margin-bottom:  1.5em">A simple online tool to convert a spreadsheet into the Humanitarian eXchange Langue (HXL). Take a look at our <a href="guide.php">quick start guide</a> or start by uploading your spreadsheet here:</p>	    


<?php    if(isset($_SESSION['loggedin'])) {   // show the upload options only if the user is logged in:  ?>
        
        <form class="alert" enctype="multipart/form-data" action="hxlate.php#" method="POST">

			<div class="control-group">
            	<label for="emergencies"><i class="icon-fire"></i> Emergency: </label>
            	<div class="controls">
            		<span class="add-on"></span>
            		<input type="text" id="emergencies" name="selectemergency" class="span3" /> <span style="margin-left: 20px;"><em>Start typing and select from the emergencies list.</em></span><br />
            		<input type="hidden" name="emergency" id="emergency">            		            		
            	</div>
            </div>

			<div class="control-group">	
            	<label class="control-label" for="report_category" ><i class="icon-tags"></i> Report category:</label>
            	<div class="controls">
        			<select id="report_category" name="report_category" class="span3">
    		        	<option value="http://hxl.humanitarianresponse.info/data/reportcategories/cluster1">Cluster 1</option>
            			<option value="http://hxl.humanitarianresponse.info/data/reportcategories/cluster2">Cluster 2</option>
            			<option value="http://hxl.humanitarianresponse.info/data/reportcategories/humanitarian_profile" selected>Humanitarian profile</option>
            			<option value="http://hxl.humanitarianresponse.info/data/reportcategories/security">Security</option>                		
            		</select>  <span style="margin-left: 20px;"><em>This determines who will verify and approve your submission.</em></span><br />            	
	           	</div>
	        </div>
			
			<div class="control-group">
			    <label class="control-label" for="translator"><i class="icon-briefcase"></i> HXL translator:</label>
			        <div class="controls">
		        		<select id="translator" name="translator" class="span3">
		              		<option value="new">Create new HXL translator</option>
		              	<?php // list user's stored translators
		              	$path = getcwd().'/mappings/'.$_SESSION['user_shortname'];
						if ($handle = opendir($path)) {
							while (false !== ($file = readdir($handle))) {
						        if ($file != "." && $file != "..") {
						            echo "<option value=\"".$file."\">Reuse translator created for ".substr($file, 0 ,-5)."</option>\n";
						        }
						    }
						    closedir($handle);
						}
						?>
		        	</select>			    	
				</div>
			</div>
			
            <div class="fileupload fileupload-new" data-provides="fileupload">
            	<label class="control-label" for="file"><i class="icon-file"></i> Select file to HXLate:</label>
  				<div class="fileupload-preview span3 uneditable-input" style="border: 1px solid #ccc"></div>
  				<span class="btn btn-file"><span class="fileupload-new">Select file</span><span class="fileupload-exists">Change</span><input name="userfile" type="file" /></span>  				
			</div>

            <br />
            <button type="submit" class="btn btn-inverse btn-large">HXLate File</button>

        </form>  

<?php 
} else {
	show_login_form();
}
?>  

    </div>	   
</div> <!-- /container -->

<?php    if(isset($_SESSION['loggedin'])) { ?>
	<script>document.getElementById('emergencies').focus()</script>

<?php
	/*
	 * Generates the autocomplete field for the emergency selection:
	 */
	function emergencyQuery(){
	    $emergencies = sparqlQuery('SELECT DISTINCT ?uri ?label WHERE {
	            ?uri a hxl:Emergency; 
	                 hxl:commonTitle ?label .
	        
	    } ORDER BY ?label');
	    
	    $label = "label";
	    $uri   = "uri";
	
		// we'll return the whole JS code here - if we only return the array of emergencies, PHP renders the array as a table instead of simply passing on the string :/
		$elist = '
		
		/*
		 * Provides the autocomplete function with an array of emergency names itself
		 * provided by the emergency query php function.
		 */
		
		var emergencies = [';
		
		foreach($emergencies as $emergency){
		    $elist .= ' { value: "'.$emergency->$label.'", uri: "'.$emergency->$uri.'"}, ' ;                 
		}
		
		
		// we're customizing the jQuery UI autocomplete a bit
		// see http://jqueryui.com/demos/autocomplete/ for the documentation
		$elist .= ' {} ]
		
		
		$("#emergencies").autocomplete({
			source: function(request, response) {
		       	var results = $.ui.autocomplete.filter(emergencies, request.term);
		     	if(!results.length){
		     		response(["<span style=\"color:red\">No matching emergencies found.</span>"]);
		     	}else{
		     		response(results);
		     	}		     	
            },
            select: function(event, ui) {
                $("#emergency").val(ui.item.uri);                
            }
        }).data("autocomplete")._renderItem = function(ul, item) {
            return $("<li></li>")
            	.data("item.autocomplete", item)
            	.append("<a>" + item.label + "<br /></a>")
            	.appendTo(ul);
        };
    
	    ';
	    
	    return $elist;
	}

	$customJS = emergencyQuery().'

	$(".fileupload").fileupload();
	';
	
	getFoot(array('jquery-ui-1.8.21.custom.min.js', 'bootstrap-fileupload.js'), $customJS ); 


} else {
	getFoot(array('jquery-ui-1.8.21.custom.min.js'), null );
}
?>