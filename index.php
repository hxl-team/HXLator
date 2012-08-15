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
        <p style="margin-top:  1.5em; margin-bottom:  1.5em">A simple online tool to convert <em>any</em> spreadsheet into the Humanitarian eXchange Langue (HXL). Take a look at our <a href="guide.php">quick start guide</a> or start by uploading your spreadsheet here:</p>	    
        
        <form class="alert" enctype="multipart/form-data" action="hxlate.php#" method="POST">

            <input type="hidden" name="user_name" value="<?php echo $user_name; ?>">
            <input type="hidden" name="user_uri" value="<?php echo $user_uri; ?>">
            <input type="hidden" name="user_organisation" value="<?php echo $user_organisation; ?>">
            <input type="hidden" name="user_organisation_uri" value="<?php echo $user_organisation_uri; ?>">
			
			<div class="control-group">
            	<label for="emergencies">Emergency: </label>
            	<div class="controls">
            		<input type="text" id="emergencies" name="selectemergency" /> <span style="margin-left: 20px;"><em>Start typing and select from the emergencies list.</em></span><br />
            		<input type="hidden" name="emergency" id="emergency">
            		<small id="emergencyuri"></small>
            	</div>
            </div>

			<div class="control-group">	
            	<label  class="control-label" for="report_category" >Report category:</label>
            	<div class="controls">
		            <select id="report_category" name="report_category">
        		        <option value="http://hxl.humanitarianresponse.info/data/reportcategories/cluster1">Cluster 1</option>
                		<option value="http://hxl.humanitarianresponse.info/data/reportcategories/cluster2">Cluster 2</option>
                		<option value="http://hxl.humanitarianresponse.info/data/reportcategories/humanitarian profile">Humanitarian profile</option>
                		<option value="http://hxl.humanitarianresponse.info/data/reportcategories/security">Security</option>                		
	            	</select> 
	           	</div>
	        </div>
			
			<div class="control-group">
			    <label class="control-label" for="translator">HXL translator:</label>
			        <div class="controls">
			           <select id="translator" name="translator">
			              <option value="new">Create new HXL translator</option>
			              <option><em>TODO: List user's existing translators</em></option>			               
			        </select>
				</div>
			</div>
			
            Select the spreadsheet to HXLate:<br />
            <input type="hidden" name="MAX_FILE_SIZE" value="5242880"><!-- 5MB -->
            <input name="userfile" type="file"><br />
            <br />
            <button type="submit" class="btn">HXLate File</button>

        </form>  
    </div>	   
</div> <!-- /container -->
<script>document.getElementById('emergencies').focus()</script>


<?php 
	$customJS = emergencyQuery();
	getFoot(array('jquery-ui-1.8.21.custom.min.js'), $customJS ); 

	
	/*
	 * Generates the autocomplete field for the emergency selection:
	 */
	function emergencyQuery()
	{
	    $emergencies = sparqlQuery('SELECT DISTINCT ?uri ?label WHERE {
	        GRAPH <http://hxl.humanitarianresponse.info/data/reference/fts-emergencies-2012> {
	            ?uri hxl:commonTitle ?label .
	        }
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
		     	
	    		response(results);
            },
            select: function(event, ui) {
                $("#emergencyuri").html("URI for this emergency: <a href=\'"+ui.item.uri+"\' target=\'_blank\'>"+ui.item.uri+"</a>");
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
?>