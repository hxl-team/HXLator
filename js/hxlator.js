console.log("Welcome to the HXLator switchboard. If you see any error messages below, we're terribly sorry. Please let us know at http://hxl.humanitarianresponse.info/hxlator/contact.php");

// add forward / backward buttons to the navigation
$('div.nav-hxlator').append('<span class="historynav pull-right"><a href="#" id="back" class="btn btn-mini disabled">&laquo; Back</a><a href="#" id="forward" class="btn btn-small disabled">Forward &raquo;</a></span>');

// ---------------------------------------------------
// The history/undo stuff
// ---------------------------------------------------

// History object – that's what we'll interact with and that's the object stack will store our stack of mapping objects:
$hxlHistory = new Object();

// this is the stack of mapping objects (see MappingTemplate.json for an example)
$hxlHistory.states = new Array();
$hxlHistory.currentState = -1; // pointer to where we are in the array with the mapping currently in use

// adds a new state (aka. mapping xls->hxl) to the history stack:
$hxlHistory.pushState = function($inputMapping){
	// clone the mapping object before we push it to the array
	// otherwise, we would store references to the same mapping over and over again 
	var $clone = $.extend(true, {}, $inputMapping);
	
	// if the pointer to the currentState is *not* at the end of the states array,
	// the user has changed something after going back. In this case, we delete all 
	// states *after* the current one before we add the new state to the array:
	if($hxlHistory.currentState < $hxlHistory.states.length-1){
		$deleteFrom = $hxlHistory.currentState + 1 ;
		$deleteTo   = $hxlHistory.states.length - 1 ;
		$hxlHistory.states.remove($deleteFrom, $deleteTo);
	}
	
	$hxlHistory.states.push($clone);				
	// set pointer to the last element in the array, i.e., the one we just added:
	$hxlHistory.currentState = $hxlHistory.states.length-1;	
	
	$hxlHistory.processMapping();
}

// TODO: this is where the magic happens...
// Depending on the state of the mapping, this function decides what is shown to the user
// no arguments required, since the method will figure out automatically what the current state is
$hxlHistory.processMapping = function(){
	$('#loading').show();
	
	console.log($hxlHistory.currentState);
	console.log($hxlHistory.states);
	
	var $mapping = $hxlHistory.states[$hxlHistory.currentState];
	
	// if the class has not been set yet, show the class pills:
	if(typeof $mapping.classuri == 'undefined'){
		selectClass($mapping);	
	}else if (typeof $mapping.samplerow == 'undefined') {
		selectRow($mapping);
	}else {
		mapProperty($mapping);
	}	

	$hxlHistory.checkLinks();
	generateRDF($mapping);		
}


// go one step back and revert to the last stored stage of the mapping
$hxlHistory.back = function(){
	$hxlHistory.currentState--;	
	$hxlHistory.processMapping();		
}

// if the user has gone back in the the mapping process, this function allows him to go forward again:
$hxlHistory.forward = function(){
	$hxlHistory.currentState++;	
	$hxlHistory.processMapping();		
}

// enables/disables the back/forward links depeding on the states array and currentState pointer
$hxlHistory.checkLinks = function(){
	
	//remove event listeners to avoid events being triggered multiple times:
	$('a#back').unbind();
	$('a#forward').unbind();
	
	// check back link
	if( $hxlHistory.currentState > 0 ){		
		// enable backward links:
		$('a#back').click(function() {
			$hxlHistory.back();
		});
		$('a#back').removeClass('disabled');
	}else{
		$('a#back').addClass('disabled');
	}
	
	// check forward link
	if( $hxlHistory.currentState < $hxlHistory.states.length -1 ){
		// enable backward links:
		$('a#forward').click(function() {
			$hxlHistory.forward();
		});
		$('a#forward').removeClass('disabled');
	}else{
		$('a#forward').addClass('disabled');
	}
	
}

// shows the class selection pills:
function selectClass($inputMapping){
	$('.hxlclass').unbind();
	$('.hxlclass-selectable').unbind();
	$('.hxlclass-expandable').unbind();
	
	// make sure we don't modify the original array entry:
	var $mapping = $.extend(true, {}, $inputMapping);
	$('.shortguide').load('classpills.php', function() {
		// hover handler to show class/property definitions in a popover
		$('.hxlclass').each(function() {
		    $(this).popover({
		        html: true,
		        placement: 'bottom'
		    });    
		}); 
		
		// click handler for the class buttons - step1
		$('.hxlclass-selectable').click(function(){
			$mapping.classuri = $(this).attr('classuri');
			$mapping.classsingular = $(this).attr('singular');
			$mapping.classplural = $(this).attr('plural');
			$hxlHistory.pushState($mapping); 
		});
		
		// click handler to expand the subclasses of a given HXL class:
		$('.hxlclass-expandable').click(function(){
			// 'un-highlight' all other LIs in this UL and hide the sub-class div
			
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
		
		$('#loading').hide();
			
	});	
}

// pick the first row with data
function selectRow($inputMapping){
	// make sure we don't modify the original array entry:
	var $mapping = $.extend(true, {}, $inputMapping);

	// fixing a little UI flux:
	$('.popover').hide();
	
	// in case the method is called again after going back
	$('.hxlatorrow').removeClass('highlight'); 
	$('.hxlatorrow').unbind();
	$('.hxlatorcell').removeClass('selected');
	$('.hxlatorcell').unbind();
	
	$('.shortguide').slideUp(function(){		
		$('.shortguide').html('<p class="lead selectedclass" style="visibility: none">Please click on the <strong>first</strong> row that contains <span class="label label-important" style="font-size: 1em">data</span> about a '+$mapping.classsingular+'/'+ $mapping.classplural +'.</p>');	
		$('.shortguide').slideDown();
		$('.hxlatorrow').unbind();
		// put a click listener on the table rows:
		$('.hxlatorrow').click(function(){
			$(this).addClass('highlight');
			$(this).addClass('first');
			$('.hxlatorrow').unbind('click'); //only allow one click
			$mapping.samplerow = $(this).attr('data-rowid'); 
			$hxlHistory.pushState($mapping); 
		});
	});
	$('#loading').hide();	
}

// show properties for class and start mapping process
function mapProperty($inputMapping){
	$('#loading').show();	
	// make sure we don't modify the original array entry:
	var $mapping = $.extend(true, {}, $inputMapping);
	
	$('.hxlatorrow').unbind();
		
	//next step: show properties: 
	$('.shortguide').slideUp(function(){
		$('.shortguide').html('<p class="lead">In HXL, any '+$mapping.classsingular+' can have the following properties (hover for explanations):</p>');	
			
		$.get('properties4class.php?classuri='+$mapping.classuri, function(data){
			$('.shortguide').append(data);	
			$('.shortguide').append('<p class="lead">Pick a cell or set of cells from this row that provide some information about one of the HXL properties listed. Use <code>shift</code> to select a range of cells. Then click the property to which the data in this cell applies. Note that a given cell (or set of cells) may address several properties. ');
			
			$('.shortguide').slideDown();
			
			//explanation popovers for hxl properties:
			// placement function makes sure the popover stay within the page, via http://stackoverflow.com/questions/10238089/how-can-you-ensure-twitter-bootstrap-popover-windows-are-visible
			// if we need this placement function for other popovers, we might want to pull it out into a separate function
			$('.hxlprop').popover({
			        html: true,
			        placement: function(tip, element) {
			            var $element, above, actualHeight, actualWidth, below, boundBottom, boundLeft, boundRight, boundTop, elementAbove, elementBelow, elementLeft, elementRight, isWithinBounds, left, pos, right;
			            isWithinBounds = function(elementPosition) {
			              return boundTop < elementPosition.top && boundLeft < elementPosition.left && boundRight > (elementPosition.left + actualWidth) && boundBottom > (elementPosition.top + actualHeight);
			            };
			            $element = $(element);
			            pos = $.extend({}, $element.offset(), {
			              width: element.offsetWidth,
			              height: element.offsetHeight
			            });
			            actualWidth = 283;
			            actualHeight = 117;
			            boundTop = $(document).scrollTop();
			            boundLeft = $(document).scrollLeft();
			            boundRight = boundLeft + $(window).width();
			            boundBottom = boundTop + $(window).height();
			            elementAbove = {
			              top: pos.top - actualHeight,
			              left: pos.left + pos.width / 2 - actualWidth / 2
			            };
			            elementBelow = {
			              top: pos.top + pos.height,
			              left: pos.left + pos.width / 2 - actualWidth / 2
			            };
			            elementLeft = {
			              top: pos.top + pos.height / 2 - actualHeight / 2,
			              left: pos.left - actualWidth
			            };
			            elementRight = {
			              top: pos.top + pos.height / 2 - actualHeight / 2,
			              left: pos.left + pos.width
			            };
			            above = isWithinBounds(elementAbove);
			            below = isWithinBounds(elementBelow);
			            left = isWithinBounds(elementLeft);
			            right = isWithinBounds(elementRight);
			            if (above) {
			              return "top";
			            } else {
			              if (below) {
			                return "bottom";
			              } else {
			                if (left) {
			                  return "left";
			                } else {
			                  if (right) {
			                    return "right";
			                  } else {
			                    return "right";
			                  }
			                }
			              }
			            }
			          }
			        });
			
			// handle selection on the highlighted table row
			$('tr.highlight > td.hxlatorcell').click(function(e) { 
			  	$(this).toggleClass('selected');
			  	// if (a) the cell has been added to the selection, (b) a cell has been selected before, and 
			  	// (c) the shift key has been pressed, mark the whole range between those two as selected 
			  	if( $(this).hasClass('selected') ) {
			  		if ( $('.hxlatorcell').hasClass('lastselected') && e.shiftKey ){
				  		$('.lastselected').addClass('range');
				  		$(this).addClass('range');
		
						// iterate through that row and mark all cells between the two .range cells as selected:
						var $mark = false;
						$('tr.highlight > td.hxlatorcell').each(function() {
							// flip selection switch at the .range cells:
							if( $(this).hasClass('range') ){
								if($mark == true){
									$mark = false;
								} else {
									$mark = true;
								}
							}
							
							if ($mark == true){
								$(this).addClass('selected');
							}
						});
						
						// clean up
					  	$('.hxlatorcell').removeClass('range');
					 }
					 
					 //mark last selected cell to enable range selection via shift-click:
					 $('.hxlatorcell').removeClass('lastselected');
					 $(this).addClass('lastselected');
					 
			  	}
			  	
			  	// enable the property buttons and click listener if any cell is selected, disable if not:
			  	if ( $('tr.highlight > td.hxlatorcell').hasClass('selected') ){
			  		$('.hxlprop').removeClass('disabled');
			  		$('.hxlprop').click(function() {
			  			mappingModal($mapping, $(this).attr('data-original-title'), $(this).attr('data-hxl-uri'));	
			  		});
			  	} else {
				  	$('.hxlprop').addClass('disabled');
				  	$('.hxlprop').unbind();
			  	}
			  		
			  
			});
			
			}).error(function() { 
				hxlError('Our server has some hiccups. We will look into that as soon as possible.');
		});
	});	
	
	$('#loading').hide();	
}

// shows and fills the mapping modal
function mappingModal($inputMapping, $propName, $propURI){
	// make sure we don't modify the original array entry:
	var $mapping = $.extend(true, {}, $inputMapping);
	
	
	var $numCells = $('td.selected').length;
	
	$('#mappingModal > .modal-header > h3').html('Mapping '+$numCells+' cells to the <em>'+$propName+'</em> property');
	
	$('#mappingModal > .modal-body').html('<p>The logic to distinguish data properties from object properties is till missing here. Let\'s do data properties for now:</p>');
	$('#mappingModal > .modal-body').append('<form class="form-horizontal"><fieldset><div class="control-group"><label class="control-label" for="mapping-type">Map to…</label><div class="controls"><select id="mapping-type"><option>Cell value</option><option>Manual input (same value for all)</option><option>Manual input (individual values)</option></select></div></div></fieldset></form>');
	
	$('#mappingModal > .modal-footer').html('<a href="#" class="btn btn-primary">Store mapping (doesn\'t do anything yet)</a><a href="#" class="btn" data-dismiss="modal">Cancel</a>');
	
	$('#mappingModal').modal('show');
}


// ---------------------------------------------------
// RDF generation
// ---------------------------------------------------

// processes a mapping, generates RDF from it and updates the preview modal
function generateRDF($mapping){
	var $turtle = "@prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> . \n@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . \n@prefix owl:  <http://www.w3.org/2002/07/owl#> . \n@prefix foaf: <http://xmlns.com/foaf/0.1/> . \n@prefix dc:   <http://purl.org/dc/terms/> . \n@prefix xsd:  <http://www.w3.org/2001/XMLSchema#> . \n@prefix skos: <http://www.w3.org/2004/02/skos/core#> . \n@prefix hxl:  <http://hxl.humanitarianresponse.info/ns/#> . \n@prefix geo:  <http://www.opengis.net/geosparql#> . \n@prefix label: <http://www.wasab.dk/morten/2004/03/label#> . \n \n";
	$.each($mapping.templates, function($uri, $triples){
		$.each($triples["triples"], function($i, $triple){
			var $datatype = "";
			if ($triple["datatype"] != undefined){
				$datatype  = "^^" + $triple["datatype"];
			}
			$turtle += $uri + " " + $triple["predicate"] + " " + $triple["object"] + $datatype + " .\n";
		});
	});
	$('#nakedturtle').html(htmlentities($turtle, 0));
	return $turtle;
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
//function enableHXLmapping(){
//	$('.hxlprop').unbind();
	// click listener for the property buttons:
//	$('.hxlprop').click(function(){
//		$('.hxlprop').unbind('click'); //only allow one click
//		$(this).addClass('btn-warning');
//
//		$('.hxlatorcell').unbind();
		// add click listener to the table cells:
//		$('.hxlatorcell').click(function(){
//			$('.hxlatorcell').unbind('click'); //only allow one click
//			$(this).addClass('selected');	
//			$('#furtherinstructions').html('Please repeat this pairwise maping until you have mapped all cells in your spreadsheet to a property. Note that a cell can also be mapped to several properties, so you might want to map the same cell several times. <a class="btn">Done?</a>');
//			$('#mappings').append('<p><code>This is a mapped triple.</code></p>');
//			$('#mappings').slideDown(); // show the box after the first cell has been mapped
//			$('.hxlprop').removeClass('btn-warning'); 
//			$('.hxlatorcell').removeClass('selected');
//			
			// TODO: add something clever to the table cell to indicate that it already has a mapping
//			enableHXLmapping();	
//		});	
//	});
//}

// ---------------------------------------------------
// Convenience functions
// ---------------------------------------------------


// a generic error display for hxlate.php. Will show $msg in a red alert box on top of the page
function hxlError($msg){
	$('.shortguide').prepend('<div class="alert alert-block alert-error fade in"><button type="button" class="close" data-dismiss="alert">×</button><h4 class="alert-heading">'+$msg+'</h4></div>');
}


// Generic SPARQL lookup function, returns JS object 
function lookup($sparqlQuery){
	console.log($sparqlQuery);
	var $endpoint = "http://hxl.humanitarianresponse.info/sparql";	
	return $.ajax({
	    url: $endpoint,
	    headers: {
	    	Accept: "application/sparql-results+json", 
	    },
	    data: { 
	    	query: $sparqlQuery 
	    },	    
	    success: function($json) {
	    	return $json;
	    },
	    error: function($jqXHR, $textStatus, $errorThrown){
	    	console.log($textStatus);
	    }	   
	});	
}


// turns our turtle code into something we can show to the user:
// courtesy of http://css-tricks.com/snippets/javascript/htmlentities-for-javascript/
function htmlentities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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


// Array Remove - By John Resig (MIT Licensed)
Array.prototype.remove = function(from, to) {
  var rest = this.slice((to || from) + 1 || this.length);
  this.length = from < 0 ? this.length + from : from;
  return this.push.apply(this, rest);
};
