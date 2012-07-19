// ---------------------------------------------------
// The history/undo stuff
// ---------------------------------------------------

// History object – that's what we'll interact with and that's the object stack will store our stack of mapping objects:
$hxlHistory = new Object();

// this is the stack of mapping objects (see MappingTemplate.json for an example)
$hxlHistory.states = new Array();
$hxlHistory.currentState = 0; // pointer to where we are in the array with the mapping currently in use

// adds a new state (aka. mapping xls->hxl) to the history stack:
$hxlHistory.pushState = function($mapping){
	// enable back/forward links:
	$('li.historynav > a').css('display', 'block');
	$hxlHistory.states.push($mapping);				
	// set pointer to the last element in the array, i.e., the one we just added:
	$hxlHistory.currentState = $hxlHistory.states.length-1;
	console.log($mapping);
//	console.log($hxlHistory.states);
//	console.log($hxlHistory.currentState);
}

// go one step back and revert to the last stored stage of the mapping
$hxlHistory.back = function(){
	if($hxlHistory.currentState > 0){
		$hxlHistory.currentState--;	
		$thisState = $hxlHistory.states[$hxlHistory.currentState];
		$hxlHistory.processMapping($thisState);
		// todo: enable forward link
	}	
}

$hxlHistory.foward = function(){
	if($hxlHistory.currentState < $hxlHistory.states.length-1){
		$hxlHistory.currentState++;	
		$thisState = $hxlHistory.states[$hxlHistory.currentState];
		$hxlHistory.processMapping($thisState);
		// todo: disable back link of we are at the first element in the array
	}			
}

$hxlHistory.processMapping = function($mapping){
	console.log("Processing mapping:");
	console.log($mapping);
	
	// todo: this is where the magic happens...
}

// finally, make sure the user doesn't go back through the browser's back button:
window.onbeforeunload = function (e) {
  var message = "You are about to leave this page and lose your current HXL mapping. If you have made a mistake in your mapping process, you can go back to the previous step by clicking the HXL back button at the top left.",
    e = e || window.event;
	  // For IE and Firefox
	if (e) {
	    e.returnValue = message;
	}
	
	// For Safari
	return message;
};

$('ul#topnav').append('<li class="historynav"><a href="#" id="back">&laquo; Back</a></li><li class="historynav"><a href="#" id="forward">Forward &raquo;</a></li>');


// ---------------------------------------------------
// RDF generation
// ---------------------------------------------------

// processes a mapping, generates RDF from it and updates the preview modal
generateRDF = function ($mapping){
	$turtle = "@prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> . \n@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . \n@prefix owl:  <http://www.w3.org/2002/07/owl#> . \n@prefix foaf: <http://xmlns.com/foaf/0.1/> . \n@prefix dc:   <http://purl.org/dc/terms/> . \n@prefix xsd:  <http://www.w3.org/2001/XMLSchema#> . \n@prefix skos: <http://www.w3.org/2004/02/skos/core#> . \n@prefix hxl:  <http://hxl.humanitarianresponse.info/ns/#> . \n@prefix geo:  <http://www.opengis.net/geosparql#> . \n@prefix label: <http://www.wasab.dk/morten/2004/03/label#> . \n \n";
	$.each($mapping.templates, function($uri, $triples){
		$.each($triples["triples"], function($i, $triple){
			$datatype = "";
			if ($triple["datatype"] != undefined){
				$datatype  = "^^" + $triple["datatype"];
			}
			$turtle += $uri + " " + $triple["predicate"] + " " + $triple["object"] + $datatype + " .\n";
		});
	});
	$('#nakedturtle').html(htmlentities($turtle, 0));
	return $turtle;
}


// ---------------------------------------------------
// Popovers
// ---------------------------------------------------


// hover handler to show class/property definitions in a popover
$('.hxlclass').each(function() {
    $(this).popover({
        html: true,
        placement: 'bottom'
    });    
}); 

// Testing the lookup function:
// var jason = lookup('SELECT * WHERE { ?a ?b ?c . } LIMIT 10');
// console.log(jason);


// ---------------------------------------------------
// User interaction handling
// ---------------------------------------------------


// click handler for the class buttons - step1
$('.hxlclass-selectable').click(function(){
	$className = $(this).attr('singular');
	$plural    = $(this).attr('plural');
	$classURI  = $(this).attr('classuri');
	step2($className, $plural, $classURI);
	hxlHistory.pushState(null);
});

// click handler to expand the subclasses of a given HXL class:
$('.hxlclass-expandable').click(function(){
	// 'un-highlight' all other LIs in this UL and hide the sub-class div
	
	// TODO this only works for going one "level" back so far 
	$(this).parent().siblings('.solo').each(function(){
		$(this).removeClass('active');	
		$subclassesof = $(this).children().first().attr('classuri');
		$('div[subclassesof*="'+$subclassesof+'"]').addClass('hxl-hidden');
	});
	
	// highlight the clicked one:
	$(this).parent().addClass('active');
	
	// show the div containing the subclasses:
	$subclassesof = $(this).attr('classuri');
	$('div[subclassesof*="'+$subclassesof+'"]').removeClass('hxl-hidden');	
});

// pick the first row with data
function step2($className, $plural, $classURI){
	$('.popover').hide();
	$('.shortguide').slideUp(function(){		
		$('.step1').remove();
		$('.shortguide').append('<div class="step2"><p class="lead selectedclass" style="visibility: none">Please click on the <strong>first</strong> row that contains data about a '+$className+'/'+ $plural +'.</p></div>');	
		$('.shortguide').slideDown();
	});
	
	step3($className, $classURI);	
}


// pick the last row with data
function step3($className, $classURI){
	$('.hxlatorrow').click(function(){
		$(this).addClass('highlight');
		$(this).addClass('first');
		$('.hxlatorrow').unbind('click'); //only allow one click
		
		$('.shortguide').slideUp(function(){
			$('.step2').remove();
			$('.shortguide').append('<div class="step3"><p class="lead selectedclass" style="visibility: none">Please click <strong>all</strong> cells in this row that identify a '+$className+'. If the whole row is about <em>one</em> '+$className+', please click the row number on the very left. </p></div>');	
			$('.shortguide').slideDown();		
			
			step4($className, $classURI);				
		});
	});
}

// select the cells in the first data row that identify instances of the selected class 
function step4($className, $classURI){
	// TODO: highlight leftmost cell!
	step5($className, $classURI);
}

// show properties for class and start mapping process
function step5($className, $classURI){
	$('.hxlatorrow').click(function(){
		// go back to the top of the page:
		$('body').scrollTop(0);		
		
		//next step: show properties: 
		$('.shortguide').slideUp(function(){
			$('.step3').remove();
			$('.shortguide').append('<div class="step5"><p class="lead">In HXL, any '+$className+' can have the following properties:</p>');	
			
			$('#loader').show();
			$.get('properties4class.php?classuri='+$classURI, function(data){
				$('.shortguide').append(data);	
				$('.hxlclass').each(function() {
				    $(this).popover({
				        html: true,
				    });    
				}); 
				$('#loader').hide();
				$('.shortguide').append('<p class="lead">Please select a </p><p class="lead" id="furtherinstructions"></p></div>');
				$('.shortguide').slideDown();
				enableHXLmapping();
			}).error(function() { 
				hxlError('<strong>Oh snap!</strong> Our server has some hiccups. We will look into that as soon as possible.');
			});
		});	
	});
}

// highlights all rows between the selected rows
function highlightSpreadsheetBlock(){	
	$hi = false;
	$(".hxlatorrow").each(function() {
		if($hi){
			$(this).addClass('highlight');			
			// TODO: add classes to the cells in this row to enable us to add a click listener in the next step			
			if($(this).hasClass('last')){
				$hi = false;
				// TODO: store the row range for the conversion process
			}
		}else{
			if($(this).hasClass('highlight')){
				$hi = true;
			}
		}				  
	});
}

// adds the click listeners to the property buttons and table cells to enable the mapping
function enableHXLmapping(){
	// click listener for the property buttons:
	$('.hxlprop').click(function(){
		$('.hxlprop').unbind('click'); //only allow one click
		$(this).addClass('btn-warning');
		// add click listener to the table cells:
		$('.hxlatorcell').click(function(){
			$('.hxlatorcell').unbind('click'); //only allow one click
			$(this).addClass('selected');	
			$('#furtherinstructions').html('Please repeat this pairwise maping until you have mapped all cells in your spreadsheet to a property. Note that a cell can also be mapped to several properties, so you might want to map the same cell several times. <a class="btn">Done?</a>');
			$('#mappings').append('<p><code>This is a mapped triple.</code></p>');
			$('#mappings').slideDown(); // show the box after the first cell has been mapped
			$('.hxlprop').removeClass('btn-warning'); 
			$('.hxlatorcell').removeClass('selected');
			
			// TODO: add something clever to the table cell to indicate that it already has a mapping
			enableHXLmapping();	
		});	
	});
}

// ---------------------------------------------------
// Convenience functions
// ---------------------------------------------------


// a generic error display for hxlate.php. Will show $msg in a red alert box on top of the page
function hxlError($msg){
	$('.shortguide').prepend('<p class="alert alert-error">'+$msg+'</p>');
	$('#loader').hide();
}


// Generic SPARQL lookup function, returns JS object 
function lookup(sparqlQuery){
	console.log(sparqlQuery);
	var endpoint = "http://hxl.humanitarianresponse.info/sparql";	
	return $.ajax({
	    url: endpoint,
	    headers: {
	    	Accept: "application/sparql-results+json", 
	    },
	    data: { 
	    	query: sparqlQuery 
	    },	    
	    success: function(json) {
	    	return json;
	    },
	    error: function(jqXHR, textStatus, errorThrown){
	    	console.log(textStatus);
	    }	   
	});	
}


// turns our turtle code into something we can show to the user:
// courtesy of http://murphys-world.dyndns.org/pages/javascript-htmlentities.php
function htmlentities(str,typ) {
  if(typeof str=="undefined") str="";
  if(typeof typ!="number") typ=2;
  typ=Math.max(0,Math.min(3,parseInt(typ)));
  var html=new Array();
  html[38]="amp"; html[60]="lt"; html[62]="gt";
  if(typ==1 || typ==3) html[39]="#039";
  if(typ==2 || typ==3) html[34]="quot";
  for(var i in html)
    eval("str=str.replace(/"+String.fromCharCode(i)+"/g,\"&"+html[i]+";\");");
  var entity=new Array(
    "nbsp","iexcl","cent","pound","curren","yen","brvbar","sect",
    "uml","copy","ordf","laquo","not","shy","reg","macr",
    "deg","plusmn","sup2","sup3","acute","micro","para","middot",
    "cedil","sup1","ordm","raquo","frac14","frac12","frac34","iquest",
    "Agrave","Aacute","Acirc","Atilde","Auml","Aring","AElig","Ccedil",
    "Egrave","Eacute","Ecirc","Euml","Igrave","Iacute","Icirc","Iuml",
    "ETH","Ntilde","Ograve","Oacute","Ocirc","Otilde","Ouml","times",
    "Oslash","Ugrave","Uacute","Ucirc","Uuml","Yacute","THORN","szlig",
    "agrave","aacute","acirc","atilde","auml","aring","aelig","ccedil",
    "egrave","eacute","ecirc","euml","igrave","iacute","icirc","iuml",
    "eth","ntilde","ograve","oacute","ocirc","otilde","ouml","divide",
    "oslash","ugrave","uacute","ucirc","uuml","yacute","thorn","yuml"
  );
  for(var i in entity)
    eval("str=str.replace(/"+String.fromCharCode(i*1+160)+"/g,\"&"+entity[i]+";\");");
  return str;
}

