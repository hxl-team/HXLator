//console.log("Welcome to the HXLator switchboard. If you see any error messages below, we're terribly sorry. Please let us know at http://hxl.humanitarianresponse.info/hxlator/contact.php");

var $debug = false;

// add forward / backward buttons to the navigation
$('div.nav-hxlator').append('<span class="historynav pull-right"><a href="#" id="back" class="btn btn-mini disabled">&laquo; Back</a><a href="#" id="forward" class="btn btn-small disabled">Forward &raquo;</a></span>');


// ---------------------------------------------------
// The history/undo stuff
// ---------------------------------------------------

// History object – that's what we'll interact with and that's the object that will store our stack of mapping objects:
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

// this is where the magic happens...
// Depending on the state of the mapping, this function decides what is shown to the user
// no arguments required, since the method will figure out automatically what the current state is
$hxlHistory.processMapping = function(){
	$('#loading').show();
	
	var $mapping = $hxlHistory.states[$hxlHistory.currentState];
	if($debug){ console.log($mapping); }
	
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
		$('.shortguide').html('<p class="lead selectedclass" style="visibility: none">Please click on the <strong>first</strong> row that contains <span class="label label-important" style="font-size: 1em">data</span> about a '+$mapping.classsingular+'/'+ $mapping.classplural +'.</p><p align="right"><i class="icon-hand-right"></i> That\'s <em>not</em> the header row, but usually the first row containing numbers.</p>');	
		$('.shortguide').slideDown();
		$('.hxlatorrow').unbind();
		// put a click listener on the table rows:
		$('.hxlatorrow').click(function(){
			$(this).addClass('highlight');
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
	$('.hxlatorcell').unbind();
	$('a#done').unbind();

	// if there are already any cells selected, 'unselect' them first:
	$('.hxlatorcell').removeClass('selected lastselected');
		
	//next step: show properties: 
	$('.shortguide').slideUp(function(){
		$('.shortguide').html('<p class="lead">In HXL, any '+$mapping.classsingular+' can have the following properties (hover for explanations):</p>');	
			
		$.get('properties4class.php?classuri='+$mapping.classuri, function(data){
			$('.shortguide').append(data);	
			
			tagMappedCellsAndProps($mapping);
			
			// if there are already any mappings (i.e., at least one tagged property button), show a different text:
			if($('a.mapped').length > 0){
				$('.shortguide').append('<p class="lead">Keep doing this (select one or more cells, then select a property) until you have mapped all cells in the selected row. Keep in mind that a cell may address several properties. Are you <a href="#" id="done" class="btn btn-info">done?</a></p><p align="right"><i class="icon-hand-right"></i> Made a mistake? You can always go back using the buttons in the top right.</p>');

				$('a#done').click(function(){
					checkProperties();
				});

			}else{
				$('.shortguide').append('<p class="lead">Pick a cell or set of cells from this row that provide some information about one of the HXL properties listed. Then click the property to which the data in this cell applies. Note that a given cell (or set of cells) may address several properties.</p><p align="right"><i class="icon-hand-right"></i> Use <code>shift</code> to select a range of cells.</p>');
			}
			
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
			            if (below) return "bottom";
			            else if (above) return "top";
			            else if (left) return "left";
			            else return "right";
			          }
			        });
			
			enableCellSelection($mapping);
			
			}).error(function() { 
				hxlError('Our server has some hiccups. We will look into that as soon as possible.');
		});
	});	
	
	$('#loading').hide();		
}

// let the user select rows in the spreadsheet for mapping
function enableRowSelection(){
	$('.shortguide').html('<p class="lead">Please select all rows now that you want to HXLate. Note that they must have the same structure as the row you have been working on so far.</p><p align="right"><i class="icon-hand-right"></i> Use <code>shift</code> again to select a range of rows.</p>');
}

// check if all properties have been mapped, show those that have not been mapped to the user in a modal:
function checkProperties(){

	$('a#selectRows').unbind();

	var $missingProps = '';
	var $numProps = 0;
	$('a.hxlprop').each(function(){
		if(!$(this).hasClass('mapped')){
			$missingProps += '<span class="label label-info missing-prop" style>'+$(this).html()+'</span> ';
			$numProps ++;
		}
	});

	// show the modal if there are any missing properties:
	if($missingProps != ''){
		$('#mappingModal > .modal-header > h3').html($numProps+' properties not mapped');
		$('#mappingModal > .modal-body').html('<p>The following properties have not been mapped yet:</p><p>'+$missingProps+'<p>If you do not have any information on these properties, go ahead and select the rows of this spreadsheet that you want to HXLate. If you do have information about any of these properties (either in the spreadsheet, or elsewhere), please go back to the mapping and fill these in.</p>');
		$('#mappingModal > .modal-footer').html('<a href="#" id="selectRows" class="btn">Select rows</a><a href="#" class="btn" data-dismiss="modal">Keep mapping</a>');
		$('#mappingModal').modal('show');

		$('a#selectRows').click(function(){
			$('#mappingModal').modal('hide');	
			enableRowSelection();	
		});

	}else{
		enableRowSelection();
	}
}

function enableCellSelection($mapping){
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
	  			mappingModal($mapping, $(this).attr('data-original-title'), $(this).attr('data-hxl-uri'), $(this).attr('data-hxl-propertytype'), $(this).attr('data-hxl-range'), $(this).attr('data-hxl-range-name') );	
	  		});
	  	} else {
		  	$('.hxlprop').addClass('disabled');
		  	$('.hxlprop').unbind();
	  	}	  			  
	});
}

// shows and fills the mapping modal
function mappingModal($inputMapping, $propName, $propURI, $propType, $propRange, $propRangeName){
	
	var $numCells = $('td.selected').length;
	
	$('#mappingModal > .modal-header > h3').html('<img src="img/loader.gif" id="modal-loading" class="pull-right" />Mapping '+$numCells+' cells to the <em>'+$propName+'</em> property');
	
	$('#mappingModal > .modal-footer').html('<i class="icon-hand-right"></i> Don\'t worry about doing anything wrong here, you can always go back to fix it later.</p><a href="#" id="storeMapping" class="btn btn-primary">Store mapping</a><a href="#" class="btn" data-dismiss="modal">Cancel</a>');
	
	if($propType == 'http://www.w3.org/2002/07/owl#DataProperty'){
		$('#mappingModal > .modal-body').html('<p>You can either <a href="#" class="btn" id="mapCellValues">map each cell to the value it contains</a> or <a href="#" class="btn" id="mapDifferentValues">map it to a different value</a>.</p><div id="value-input" style="display: none"></div>');
		
		$('#mapCellValues').click(function(){
			$(this).addClass('btn-info');
			$('#mapDifferentValues').removeClass('btn-info');
			mapCellValues($inputMapping, $propName, $propURI, $propType, $propRange);
		});
		
		$('#mapDifferentValues').click(function(){
			$(this).addClass('btn-info');
			$('#mapCellValues').removeClass('btn-info');
			mapDifferentValues($inputMapping, $propName, $propURI, $propType, $propRange);
		});
		
	} else if ($propType == 'http://www.w3.org/2002/07/owl#ObjectProperty'){
		$('#mappingModal > .modal-body').html('<p>This property should refer to a <em>'+$propRangeName+' </em> from one of our reference lists.</p><div id="value-input" style="display: none"></div>');
		
		mapWithURILookup($inputMapping, $propName, $propURI, $propType, $propRange, $propRangeName);
		
		
		
	} else { // Something's going wrong...
		$('#mappingModal > .modal-body').html('<p>We can\'t handle the property type '+$propType+'</p>');
	}
		
	$('#mappingModal').modal('show');
}

// generates the modal contents to map object properties (with URI lookup)
function mapWithURILookup($inputMapping, $propName, $propURI, $propType, $propRange, $propRangeName){
	// make sure we don't modify the original array entry:
	var $mapping = $.extend(true, {}, $inputMapping);
	
	$('#value-input').slideUp(function(){
		$('#value-input').html('');
		$('.selected').each(function(){
			$('#value-input').append('<hr /><em>'+$propName+'</em> for cell <code>'+$(this).attr('data-cellid')+'</code><br><input type="text" class="value-input" placeholder="Start typing to search reference list" id="valuefor-'+$(this).attr('data-cellid')+'" data-value-subject="'+$(this).attr('data-cellid')+'" data-cellid="'+$(this).attr('data-cellid')+'"> or <a href="#" class="btn btn-small cell-input" data-cellid="'+$(this).attr('data-cellid')+'">map from spread sheet</a><br /><small data-cellid="'+$(this).attr('data-cellid')+'" class="uri valuefor-'+$(this).attr('data-cellid')+'"></small>');	

			// show 'copy' button if more than one field is selected:
			if ( $('.selected').length > 1){
				$('#value-input').append('<br /><a href="#" class="btn btn-small disabled adoptforall valuefor-'+$(this).attr('data-cellid')+'" data-cellid="'+$(this).attr('data-cellid')+'" style="margin-top 10px">Adopt this value for all cells</a>');
			}
			
		});
		
		// copying values over to all input fields:
		if ( $('.selected').length > 1){
			$('.adoptforall').click(function(){

				var $selecta = '.value-input[data-cellid='+$(this).attr('data-cellid')+']';

				var $copyVal      = $($selecta).val();
				var $copyURI      = $($selecta).attr('data-value-object');
				var $copyFunction = $($selecta).attr('data-function');
				var $hint         = $('small[data-cellid='+$(this).attr('data-cellid')+']').html();
				
				$('.value-input').each(function(){
					$(this).val($copyVal);
					$(this).attr('data-value-object', $copyURI);
					$(this).attr('data-function', $copyFunction);
				});
					
				$('.uri').each(function(){
					$(this).html($hint);
				});		  	
			});	
		}
			


		// enable value selection from spreadsheet
		$('.cell-input').click(function(){			
			$('.hxlatorcell').unbind();
			var $subjectcell = $(this).attr('data-cellid');

			// hide the modal and allow the user to select the cell:
			$('#mappingModal').modal('hide');
			$('.shortguide').slideUp();
			$('.shortguide').after('<div class="container cell-instructions"><div class="alert alert-info">Please click the cell that contains the value for the <em>'+$propName+'</em> property of cell <code>'+$(this).attr('data-cellid')+'</code>. We will then take you back to the mapping window.</div></div>');
			
			var $target = $(this).attr('data-cellid');
			
			$('.hxlatorcell').click(function(){
				
				// the input field to write into:
				var $selecta = 'input[data-value-subject="'+$subjectcell+'"]';

				$($selecta).val($(this).html()+' (via cell '+$(this).attr('data-cellid')+')');
				$('small[data-cellid="'+$subjectcell+'"]').html('We will ask you to select the URI for this term in the next step.');
				// add the @lookup tag to the value to indicate that we'll need to look up the values for these at the end
				$($selecta).attr('data-value-object', $(this).attr('data-cellid'));
				$($selecta).attr('data-function', '@lookup');
				
				$('.adoptforall[data-cellid="'+$subjectcell+'"]').removeClass('disabled');
				
				$('#mappingModal').modal('show');
				$('.shortguide').slideDown();
				$('.cell-instructions').remove();
				// unbind all listeners, then bind the select listener for the cells again ('mark orange')
				$('.hxlatorcell').unbind();				
				enableCellSelection($mapping);
			});
			
		});
		
		
		// add autocomplete to the input fields:
		$('.value-input').autocomplete({
				source: function( request, response ) {
					
					// select the query based on the range of this property:
					var $query = '';
				
					if($propRange == 'http://hxl.humanitarianresponse.info/ns/#AdminUnit' || $propRange == 'http://hxl.humanitarianresponse.info/ns/#Country' || $propRange == 'http://www.opengis.net/geosparql#Feature'){
				
						$query = 'prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> prefix hxl: <http://hxl.humanitarianresponse.info/ns/#> SELECT * WHERE { ?value rdf:type/rdfs:subClassOf* <'+$propRange+'> . ?value hxl:featureName ?label . ?value hxl:atLocation* ?location . ?location a hxl:Country ; hxl:featureName ?country .   FILTER regex(?label, "'+request.term+'", "i") } ORDER BY ?label';

					}else if ($propRange == 'http://hxl.humanitarianresponse.info/ns/#AdminUnitLevel'){
						
						$query = 'prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> prefix hxl: <http://hxl.humanitarianresponse.info/ns/#> SELECT * WHERE { ?value rdf:type/rdfs:subClassOf* <'+$propRange+'> . ?value hxl:adminUnitLevelTitle ?label . FILTER regex(?label, "'+request.term+'", "i") } ORDER BY ?label';
						
					}else if ($propRange == 'http://hxl.humanitarianresponse.info/ns/#HXLer'){

						$query = 'prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> prefix hxl: <http://hxl.humanitarianresponse.info/ns/#> SELECT * WHERE { ?value rdf:type/rdfs:subClassOf* <'+$propRange+'> . ?value <http://xmlns.com/foaf/0.1/name> ?label . FILTER regex(?label, "'+request.term+'", "i") } ORDER BY ?label';		
										
					}else if ($propRange == 'http://hxl.humanitarianresponse.info/ns/#Emergency'){

						$query = 'prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> prefix hxl: <http://hxl.humanitarianresponse.info/ns/#> SELECT * WHERE { ?value rdf:type/rdfs:subClassOf* <'+$propRange+'> . ?value hxl:commonTitle ?label . FILTER regex(?label, "'+request.term+'", "i") } ORDER BY ?label';								
					}else{
						
						$query = 'prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> prefix hxl: <http://hxl.humanitarianresponse.info/ns/#> SELECT * WHERE { ?value rdf:type/rdfs:subClassOf* <'+$propRange+'> . ?value hxl:title ?label . FILTER regex(?label, "'+request.term+'", "i") } ORDER BY ?label';		

					}
					
					//console.log($query);
					
					
					$('#modal-loading').show();
					$.ajax({
						url: 'http://hxl.humanitarianresponse.info/sparql',
						headers: {
							Accept: 'application/sparql-results+json'
						},
						data: { 
							query: $query 
						},							
						success: function( data ) {
							response( $.map( data.results.bindings, function( result ) {
								
								// special handling of Features to show the country they are in:
								if($propRange == 'http://hxl.humanitarianresponse.info/ns/#AdminUnit' || $propRange == 'http://hxl.humanitarianresponse.info/ns/#Country' || $propRange == 'http://www.opengis.net/geosparql#Feature'){
									return {
										value: result.label.value+ ' ('+result.country.value+')',
										uri: result.value.value
									}
								}else{
									return {
										value: result.label.value,
										uri: result.value.value
									}
								}
								
							}));
							$('#modal-loading').hide();
						},
						error: function($jqXHR, $textStatus, $errorThrown){
							console.error($textStatus);
						}
					});
				},
				minLength: 1,
				select: function( event, ui ) {
					// show the URI to the user 
					$('small.'+$(this).attr('id')).html(('URI for this '+$propRangeName+': <a href="'+ui.item.uri+'" target="_blank">'+ui.item.uri+'</a>'));
					// ...and store it in a data attribute of the input field
					$(this).attr('data-value-object', ui.item.uri);
					
					$('a.'+$(this).attr('id')).removeClass('disabled');
				}
			});
		
		$('#value-input').slideDown();
		addPropertyMappings($mapping, $propURI);
		
	});
	
}


// generates the modal contents to map data properties to values other than the cell contents
function mapDifferentValues($inputMapping, $propName, $propURI, $propType, $propRange){

	// make sure we don't modify the original array entry:
	var $mapping = $.extend(true, {}, $inputMapping);
	
	$('#value-input').slideUp(function(){
		// TODO: show value(s) in input field(s) if already stored in mapping
		// (e.g. after going back)
		$('#value-input').html('');
		$('.selected').each(function(){
			$('#value-input').append('<hr /><em>'+$propName+'</em> for cell <code>'+$(this).attr('data-cellid')+'</code><br><input type="text" class="value-input" placeholder="Enter value here" id="valuefor-'+$(this).attr('data-cellid')+'" data-value-subject="'+$(this).attr('data-cellid')+'"> or <a href="#" class="btn btn-small cell-input" data-cellid="'+$(this).attr('data-cellid')+'">select from spread sheet</a>');	

			//show 'copy' button if there is more than one cell selected
			if($('.selected').length > 1){
			 	$('#value-input').append('<br /><a href="#" class="btn btn-small disabled adoptforall" data-cellid="'+$(this).attr('data-cellid')+'">Adopt this value for all cells</a>');
			 
			 	var $cell = $(this).attr('data-cellid');
			 	var $selecta = 'input[data-value-subject="'+ $cell +'"]';

				$($selecta).keyup(function(){
					var $btnselecta = $('a.adoptforall[data-cellid="'+$cell+'"]');
					if($(this).val() == ''){
				  		$($btnselecta).addClass('disabled');
				  	}else{
				  		$($btnselecta).removeClass('disabled');
				  	}
			  	});

			  	$('.adoptforall').click(function(){
					// copy value to other input fields

					var $copyVal = $($selecta).val();
					var $copyObj = $($selecta).attr('data-value-object');

					$('.value-input').each(function(){
						$(this).val($copyVal);
						$(this).attr('data-value-object', $copyObj);
					})			  	
				});	
			}
		});			
		
		// enable value selection from spreadsheet
		$('.cell-input').click(function(){			
			$('.hxlatorcell').unbind();
			var $subjectcell = $(this).attr('data-cellid');

			// hide the modal and allow the user to select the cell:
			$('#mappingModal').modal('hide');
			$('.shortguide').slideUp();
			$('.shortguide').after('<div class="container cell-instructions"><div class="alert alert-info">Please click the cell that contains the value for the <em>'+$propName+'</em> property of cell <code>'+$(this).attr('data-cellid')+'</code>. We will then take you back to the mapping window.</div></div>');
			
			var $target = $(this).attr('data-cellid');
			
			$('.hxlatorcell').click(function(){
				
				// the input field to write into:
				var $selecta = 'input[data-value-subject="'+$subjectcell+'"]';

				$($selecta).val($(this).html()+' (via cell '+$(this).attr('data-cellid')+')');
				$($selecta).attr('data-value-object', $(this).attr('data-cellid'));
				$($selecta).trigger('keyup');
				
				$('#mappingModal').modal('show');
				$('.shortguide').slideDown();
				$('.cell-instructions').remove();
				// unbind all listeners, then bind the select listener for the cells again ('mark orange')
				$('.hxlatorcell').unbind();				
				enableCellSelection($mapping);
			});
			
		});
		
		
		$('#value-input').slideDown();
		addPropertyMappings($mapping, $propURI);		
				
	});	
	
}


// automatically generates triple objects for the selected property from the selected cells
function mapCellValues($inputMapping, $propName, $propURI, $propType, $propRange){
	
	// make sure we don't modify the original array entry:
	var $mapping = $.extend(true, {}, $inputMapping);
	
	$('#value-input').slideUp(function(){
		$('#value-input').html('');
		
		$('.selected').each(function(){
			$('#value-input').append('<hr /><p><em>'+$propName+'</em> for cell <code>'+$(this).attr('data-cellid')+'</code><br><input type="text" class="value-input" readonly value="'+$(this).html()+' (via cell '+$(this).attr('data-cellid')+')" data-value-subject="'+$(this).attr('data-cellid')+'" data-value-object="'+$(this).attr('data-cellid')+'" id="valuefor-'+$(this).attr('data-cellid')+'">');	
		});
		
		$('#value-input').slideDown();
		addPropertyMappings($mapping, $propURI);				
	});	
}

// adds a click listener to the "store mapping" button in the mapping modal,
// adds the data properties shown in the modal to the mapping
// and pushes the mapping to the mappings stack
function addPropertyMappings($mapping, $propURI){

	$('#storeMapping').unbind(); // remove any old listeners
	$('#storeMapping').click(function(){
		
		// store all @lookup values that are not in the lookup table yet in an array:
		var $nolook = new Array();
		
		// iterate through all input fields 
		$('.value-input').each(function(){
			var $uri = '@uri '+$(this).attr('data-value-subject');
			
			// if there are no mappings for this URI yet, add this node to the JSON tree:
			if($mapping.templates[$uri] == undefined){
				$mapping.templates[$uri] = new Object();
				$mapping.templates[$uri].triples = new Array();						
			}
			
			// add the triples:
			var $index =  $mapping.templates[$uri].triples.length;
			$mapping.templates[$uri].triples[$index] = new Object();
			$mapping.templates[$uri].triples[$index]["predicate"] = $propURI;
			
			// store a mapping depending on input field metadata
			if($(this).attr('data-value-object') == undefined){
				
				// data property with direct input
				$mapping.templates[$uri].triples[$index]['object'] = $(this).val();
				
			} else if($(this).attr('data-value-object').indexOf('http') == 0){
			
				// object property that has already been looked up
				$mapping.templates[$uri].triples[$index]['object'] = '<'+$(this).attr('data-value-object')+'>';
			
			} else if($(this).attr('data-function') == '@lookup'){
				
				// object property with lookup at the end of the mapping process
				$mapping.templates[$uri].triples[$index]["object"] = '@lookup '+$(this).attr('data-value-object');

				// check if the term to look up is already in our lookup dictionary; 
				// if not, we save the term in an array and send the user to (yet anther) mapping // page where s/he can assign URIs to the terms not in the dictionary so far
				// check if we already have the term in our lookup "dictionary" (or already in the $nolook array)
				var $lookupterm = getCellContents($(this).attr('data-value-object'));
				if($mapping.lookup[$lookupterm] == undefined && $.inArray($lookupterm, $nolook)){
					$nolook.push($lookupterm)
				} 

			} else {
				
				// data property that takes the value from the spreadsheet
				$mapping.templates[$uri].triples[$index]["object"] = '@value '+$(this).attr('data-value-object');
				
			}
										
		});
		
		// push mapping to mapping stack:
		$hxlHistory.pushState($mapping);
			
		// check the $nolook array: if it's empty, we are good and we can simply store the mapping.
		// if it does contain any terms, we'll send the user over to a new modal, where s/he can look them up:
		if($nolook.length == 0){
			// close modal:
			$('#mappingModal').modal('hide');
		} else {
			// send to lookup modal
			lookUpModal($mapping, $nolook);
		}
		
	});
}

// generates a modal that allows the user to look up URIs for all terms in $missing and 
function lookUpModal($inputMapping, $missing){

	// make sure we don't modify the original array entry:
	var $mapping = $.extend(true, {}, $inputMapping);
	

	// hide and replace the old modal contents
	$('#mappingModal > .modal-header > h3').slideUp(function(){
		var $no = $missing.length;
		if($no == 1)
			$no = "one term";
		else
			$no += " terms";

		$(this).html('<img src="img/loader.gif" id="modal-loading" class="pull-right" />URI lookup for '+$no);
		$('#modal-loading').show();
		$(this).slideDown();
	});
	
	
	$('#mappingModal > .modal-footer').slideUp(function(){
		$(this).html('<i class="icon-hand-right"></i> Don\'t worry about doing anything wrong here, you can always go back to fix it later.</p><a href="#" id="storeLookUps" class="btn btn-primary">Store URIs</a><a href="#" class="btn" data-dismiss="modal">Cancel</a>');		
		$(this).slideDown();
	});
	
	
	$('#mappingModal > .modal-body').slideUp(function(){
		$(this).html('<p>Please select one URI from our reference lists for each value that you have selected in your spreadsheet. We try to propose URIs that match the terms; if that does not work, you can also look them up yourself.</p><hr />');
		$(this).append('<div class="row"><div class="span2"><h3>Term</h3></div><div class="span3"><h3>URI</h3></div></div><hr />');

		$.each($missing, function($i, $term){
			$('#mappingModal > .modal-body').append('<div class="row"><div class="span2"><h3><code>'+$term+'</code></h3></div><div class="span3" for-term="'+$term+'"></div></div><hr />');

			$query = 'prefix skos: <http://www.w3.org/2004/02/skos/core#> prefix hxl: <http://hxl.humanitarianresponse.info/ns/#> prefix dct: <http://purl.org/dc/terms/>  prefix foaf: <http://xmlns.com/foaf/0.1/> prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> SELECT DISTINCT ?uri ?label ?typelabel WHERE { ?uri a ?type . { ?type skos:prefLabel ?typelabel } UNION { ?type rdfs:label ?typelabel } { ?uri hxl:featureRefName ?label } UNION { ?uri hxl:commonTitle ?label }UNION { ?uri dct:title ?label } UNION { ?uri foaf:name ?label } UNION { ?uri hxl:abbreviation ?label } UNION { ?uri hxl:adminUnitLevelTitle ?label } UNION { ?uri hxl:orgName ?label } UNION { ?uri hxl:title ?label } FILTER regex(?label, "'+$term+'", "i") } ORDER BY ?label';
				// console.log($query);

			$.ajax({
				url: 'http://hxl.humanitarianresponse.info/sparql',
				headers: {
					Accept: 'application/sparql-results+json'
				},
				data: { 
					query: $query 
				},							
				success: function( data ) {
					$.each(data.results.bindings, function(i, result ) {
						$('div[for-term="'+$term+'"]').append('<label class="radio"><input type="radio" name="'+$term+'" class="rdio" value="'+result.uri.value+'">'+result.label.value+' ('+result.typelabel.value+')<br /><small><a href="'+result.uri.value+'" target="_blank">'+result.uri.value+'</a></small></label>');
					});

					// if there are no results:
					if(data.results.bindings.length == 0){
						$('div[for-term="'+$term+'"]').append('<p class="'+$term+'">No suggestion found for this term, you can try to find a URI yourself:</p>');
						$('div[for-term="'+$term+'"]').append('<input type="text" class="uri-search" placeholder="Start typing to search reference list" for-term="'+$term+'" />');
					}else{
						$('div[for-term="'+$term+'"]').append('<p class="'+$term+'">If the correct URI is not among the suggestions, you can try to find it yourself:</p>');
						$('div[for-term="'+$term+'"]').append('<label class="radio"><input type="radio" name="'+$term+'"  class="rdio" value="@userlookup"><input type="text" readonly class="uri-search" placeholder="Start typing to search reference list" for-term="'+$term+'" /></label>');
					}

					// make the radio buttons next to the input boxes enable the corresponding text input box:
					$('input[name="'+$term+'"]').change(function(){
						
						if($('input[name="'+$term+'"]:checked').val() == '@userlookup'){
							// if the radio button next to the text box is clicked, toggle readonly:
							$('input[for-term="'+$term+'"]').attr('readonly', false);
						}else{
							$('input[for-term="'+$term+'"]').val('');
							$('input[for-term="'+$term+'"]').attr('readonly', true);
						}
						
					});
		

					// add autocomplete to the search field:
					$('input[for-term="'+$term+'"]').autocomplete({
						source: function( request, response ) {
							
							// select the query based on the range of this property:
							var $query = 'prefix skos: <http://www.w3.org/2004/02/skos/core#> prefix hxl: <http://hxl.humanitarianresponse.info/ns/#> prefix dct: <http://purl.org/dc/terms/>  prefix foaf: <http://xmlns.com/foaf/0.1/> prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> SELECT DISTINCT ?uri ?label ?typelabel WHERE { ?uri a ?type . { ?type skos:prefLabel ?typelabel } UNION { ?type rdfs:label ?typelabel } { ?uri hxl:featureRefName ?label } UNION { ?uri hxl:commonTitle ?label }UNION { ?uri dct:title ?label } UNION { ?uri foaf:name ?label } UNION { ?uri hxl:abbreviation ?label } UNION { ?uri hxl:adminUnitLevelTitle ?label } UNION { ?uri hxl:orgName ?label } UNION { ?uri hxl:title ?label } FILTER regex(?label, "'+request.term+'", "i") } ORDER BY ?label';

							//console.log($query);
							
							
							$('#modal-loading').show();
							$.ajax({
								url: 'http://hxl.humanitarianresponse.info/sparql',
								headers: {
									Accept: 'application/sparql-results+json'
								},
								data: { 
									query: $query 
								},							
								success: function( data ) {
									response( $.map( data.results.bindings, function( result ) {
																				
										return {
											value: result.label.value+' ('+result.typelabel.value+')',
											uri: result.uri.value
										}
										
									}));
									$('#modal-loading').hide();
								},
								error: function($jqXHR, $textStatus, $errorThrown){
									console.error($textStatus);
								}
							});
						},
						minLength: 1,
						select: function( event, ui ) {
							// Add one option to the radio list and select it:
							$('<label class="radio"><input type="radio" name="'+$term+'" checked class="rdio" value="'+ui.item.uri+'">'+ui.item.value+'<br /><small><a href="'+ui.item.uri+'" target="_blank">'+ui.item.uri+'</a></small></label>').insertBefore('p.'+$term);
							// empty and deselect input box by triggering change event
							$('input[name="'+$term+'"]').change(); 

						}
					});

							// if this is the last term, hide the loader gif:
							if($i == $missing.length-1){
								$('#modal-loading').hide();
							}							
						},
						error: function($jqXHR, $textStatus, $errorThrown){
							console.error($textStatus);
						}
					});
			
		});

		
		$('#storeLookUps').click(function(){
			// fetch all selected URIs and add them to the mapping object:
			$('input:checked').each(function(){
				$mapping.lookup[$(this).attr("name")] = $(this).attr("value");				
			});
			
			// push mapping to mapping stack:
			$hxlHistory.pushState($mapping);
		
			$('#mappingModal').modal('hide');
		});

		$(this).slideDown();
	});
}

// fetches the cell contents from the table cell with id $cellID
function getCellContents($cellID){
	if ($('td[data-cellid="'+$cellID+'"]').length == 0){
		console.error('getCellContents failed: There is no cell with ID '+$cellID);
		return '';		
	} else {
		return $('td[data-cellid="'+$cellID+'"]').html();
	} 
}

// ---------------------------------------------------
// RDF generation
// ---------------------------------------------------

// processes a mapping, generates RDF from it and updates the preview modal
function generateRDF($inputMapping){
	// we'll be manipulation the mapping a bit, so we copy it first to make sure the original remains untouched:
	var $mapping = $.extend(true, {}, $inputMapping);

	var $turtle = '@prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> . \n@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> . \n@prefix owl:  <http://www.w3.org/2002/07/owl#> . \n@prefix foaf: <http://xmlns.com/foaf/0.1/> . \n@prefix dc:   <http://purl.org/dc/terms/> . \n@prefix xsd:  <http://www.w3.org/2001/XMLSchema#> . \n@prefix skos: <http://www.w3.org/2004/02/skos/core#> . \n@prefix hxl:  <http://hxl.humanitarianresponse.info/ns/#> . \n@prefix geo:  <http://www.opengis.net/geosparql#> . \n@prefix label: <http://www.wasab.dk/morten/2004/03/label#> . \n \n';
	$.each($mapping.templates, function($uri, $triples){
		
		// first go through the whole mapping object once and replace all @value and @lookup occurrences with the actual values from the spreadsheet:
		$.each($triples['triples'], function($i, $triple){

			// values from spreadsheet:
			if($triple['object'].indexOf('@value') == 0){				
				$triple['object'] = getCellContents($triple['object'].substr(7));				
			}

			// values from spreadsheet, then map those to existing URIs:
			if($triple['object'].indexOf('@lookup') == 0){				
				var $lookupterm = getCellContents($triple['object'].substr(8));

				// check if we already have the term in our lookup "dictionary"
				if($mapping.lookup[$lookupterm] == undefined){
					//console.log('No @lookup found for "'+$lookupterm+'"');
				} else {
					$triple['object'] = $mapping.lookup[$lookupterm];
				}
			}			
		});
		
		// this is the case if the URI is already there:
		$resuri = $uri;

		// generate a URI if the $uri is not there yet (tagged with "@uri")
		// (overwriting what we had before)
		if($uri.indexOf('@uri') == 0){
		
			// go through all properties of the resource and generate a URI based on our URI patterns document:
			// https://docs.google.com/document/d/1-9OoF5vz71qPtPRo3WoaMH4S5J1O41ITszT3arQ5VLs/edit
			
			var $classslug = $mapping.classplural.toLowerCase().replace( /\s/g, '' );

			// populations:
			if($mapping.classuri == 'hxl:Population' || $mapping.classuri == 'hxl:AffectedPopulation' || $mapping.classuri == 'hxl:TotalPopulation' || $mapping.classuri == 'hxl:Casualty' || $mapping.classuri == 'hxl:Displaced' || $mapping.classuri == 'hxl:NonDisplaced' || $mapping.classuri == 'hxl:Death' || $mapping.classuri == 'hxl:Injury' || $mapping.classuri == 'hxl:Missing' || $mapping.classuri == 'hxl:IDP' || $mapping.classuri == 'hxl:Others' || $mapping.classuri == 'hxl:RefugeesAsylumSeekers' || $mapping.classuri == 'hxl:HostPopulation' || $mapping.classuri == 'hxl:NonHostPopulation') {

				var $loc = new Date().getTime();
				var $sex = 'unknown';
				var $age = '';

				// check whether location, sex and age categories are set:
				$.each($triples['triples'], function($i, $triple){
					if ($triple['predicate'] == 'hxl:currentLocation'){
						// grab URI and remove < and >
						var $place = $triple['object'].substr(1, $triple['object'].length-2);
						// strip the country and p-code from the URI (last two parts of URI):
						var $placeURIparts = $place.split('/');
						$loc = $placeURIparts[$placeURIparts.length - 2] + '/' + $placeURIparts[$placeURIparts.length - 1];
					}else if ($triple['predicate'] == 'hxl:sexCategory'){
						// grab URI and remove < and >
						var $sexCategory = $triple['object'].substr(1, $triple['object'].length-2);
						var $sexCategoryURIparts = $sexCategory.split('/');
						$sex = $sexCategoryURIparts[$sexCategoryURIparts.length -1];	
					}else if ($triple['predicate'] == 'hxl:ageGroup'){
						// grab URI and remove < and >
						var $ageGroup = $triple['object'].substr(1, $triple['object'].length-2);
						var $ageGroupURIparts = $ageGroup.split('/');
						$age = '-'+$ageGroupURIparts[$ageGroupURIparts.length -1 ];
					}
				});

				var $resuri = '<http://hxl.humanitarianresponse.info/data/' + $classslug + '/'+ $loc + '/'+ $sex + $age + '>';

			} else if($mapping.classuri == 'hxl:Emergency') {
				
				// random GLIDE number for now
				var $glide = 'unknown'-+new Date().getTime();

				// check whether the hasGLIDEnumber property is set:
				$.each($triples['triples'], function($i, $triple){
					if ($triple['predicate'] == 'hxl:hasGLIDEnumber'){
						$glide = $triple['object'];						
					}
				});
				
				

    		} else if($mapping.classuri == 'hxl:APL') {

				var $loc = new Date().getTime();
				var $pcode = new Date().getTime();
				
				// check whether location, sex and age categories are set:
				$.each($triples['triples'], function($i, $triple){
					if ($triple['predicate'] == 'hxl:atLocation'){
						// grab URI and remove < and >
						var $place = $triple['object'].substr(1, $triple['object'].length-2);
						// strip the country and p-code from the URI (last two parts of URI):
						var $placeURIparts = $place.split('/');
						// we use only the country code
						$loc = $placeURIparts[$placeURIparts.length - 2];
					}else if ($triple['predicate'] == 'hxl:pcode'){
						// grab URI and remove < and >
						$pcode = $triple['object'];	
					}
				});

    			// example: http://hxl.humanitarianresponse.info/data/locations/apl/bfa/UNHCR-POC-80
    			var $resuri = '<http://hxl.humanitarianresponse.info/data/locations/apl/' + $loc + '/' + $pcode + '>';			

    		} else if($mapping.classuri == 'hxl:Organisation') {

    			// random acronym for now:
    			var $acro = 'unknown'-+new Date().getTime();

    			// check if the acronym is set:
    			$.each($triples['triples'], function($i, $triple){
    				if ($triple['predicate'] == 'hxl:abbreviation'){
    					// remove blanks, just in case...
    					$acro = $triple['object'].toLowerCase().replace( /\s/g, '' );;					
    				}
    			});

    			var $resuri = '<http://hxl.humanitarianresponse.info/data/' + $classslug + '/'+ $acro + '>';

    		} else {
    			console.error('Error during URI generation');
    			$resuri = '<http://some.error/crap>'; 
    		}
  

			// type this URI:
			$turtle += $resuri + ' rdf:type ' + $mapping.classuri + ' .\n';
		}
	
		$.each($triples['triples'], function($i, $triple){
			
			// handling the triples object:
			var $object = '';
			if($triple['object'].indexOf('<http') == 0){ // object property
				$object = $triple['object'];
			}else if($triple['object'].indexOf('http') == 0){ // object property
				$object = '<'+$triple['object']+'>';
			}else{ // data property
				$object = '"'+$triple['object']+'"';
			}
		
			
			var $datatype = '';
			if ($triple['datatype'] != undefined){
				$datatype  = '^^' + $triple['datatype'];
			}
			
			// add the triple:			
			$turtle += $resuri + ' ' + $triple['predicate'] + ' ' + $object + $datatype + ' .\n';

		});
	});
	// update the preview modal:
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



// Marks all cells and properties that have already been mapped wit a green dot
function tagMappedCellsAndProps($mapping){
	
	// remove all, in case we're coming back:
	$('td.hxlatorcell').removeClass('mapped');
	$('a.hxlprop').removeClass('mapped');

	// iterate through mapping and count occurrences of cells and properties:
	$.each($mapping.templates, function($uri, $triples){
		
		// find cell mappings in subject URIs:
		if($uri.indexOf('@uri') == 0){
			$('td.hxlatorcell[data-cellid="'+$uri.substr(5)+'"]').addClass('mapped');			
		}

		// find cell mappings in object URIs, and list properties 
		$.each($triples['triples'], function($i, $triple){

			$('a.hxlprop[data-hxl-uri="'+$triple['predicate']+'"]').addClass('mapped');

			// values from spreadsheet:
			if($triple['object'].indexOf('@value') == 0){				
				$('td.hxlatorcell[data-cellid="'+$triple['object'].substr(7)+'"]').addClass('mapped');							
			}

		});
	});
}


// ---------------------------------------------------
// Convenience functions
// ---------------------------------------------------


// a generic error display for hxlate.php. Will show $msg in a red alert box on top of the page
function hxlError($msg){
	$('.shortguide').prepend('<div class="alert alert-block alert-error fade in"><button type="button" class="close" data-dismiss="alert">×</button><h4 class="alert-heading">'+$msg+'</h4></div>');
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