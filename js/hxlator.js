// hover handler to show class/property definitions
$('.hxlclass').each(function() {
    $(this).popover({
        html: true,
    });    
}); 

$('.hxlclass').click(function(){
	$className = $(this).attr('data-original-title');
	$classURI = $(this).attr('classuri');
	$('.step1').slideUp(function(){
		$('.shortguide').append('<div class="step2"><p class="lead selectedclass" style="visibility: none">Please click on the <em>topmost</em> row that contains data for a '+$className+'.</p></div>');	
	});

	
	$('.hxlatorrow').click(function(){
		$(this).addClass('highlight');
		$(this).addClass('first');
		$('.hxlatorrow').unbind('click'); //only allow one click
		
		$('.step2').slideUp(function(){
			$('.shortguide').append('<div class="step3"><p class="lead selectedclass" style="visibility: none">Please click on the <em>last</em> row that contains data for a '+$className+'.</p></div>');	
			
			$('.hxlatorrow').click(function(){
				$(this).addClass('highlight');
				$(this).addClass('last');
				$('.hxlatorrow').unbind('click'); //only allow one click
				
				// highlight all rows between the selected rows:
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
				
				// go back to the top of the page:
				$('body').scrollTop(0);		
				
				//next step: show properties: 
				$('.step3').slideUp(function(){
					$('.shortguide').append('<div class="step4"><p class="lead selectedclass" style="visibility: none">We will now go through the first row in this block and map each element to an HXL property. In HXL, any '+$className+' can have the following properties:</p></div>');	
					
					$('#loader').show();
					$.get('properties4class.php?classuri='+$classURI, function(data){
						$('.shortguide').append(data);	
								$('.hxlclass').each(function() {
						    $(this).popover({
						        html: true,
						    });    
						}); 
						$('#loader').hide();
					}).error(function() { 
						hxlError('<strong>Oh snap!</strong> Our server has some hiccups. We will look into that as soon as possible.');
					});
				});	
			});
		});
	});
});



// a generic error display for hxlate.php. Will show $msg in a red alert box on top of the page
function hxlError($msg){
	$('.shortguide').prepend('<p class="alert alert-error">'+$msg+'</p>');
	$('#loader').hide();
}