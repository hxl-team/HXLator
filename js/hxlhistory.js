// History object – that's what we'll interact with and that's the object stack will store our stack of mapping objects:
var hxlHistory = new Object();

// this is the stack of mapping objects (see MappingTemplate.json for an example)
var states = new Array();

// adds a new state to the history, either via history.js (for html5) or with a HXL-specific fallback for HTML4 browsers: 
hxlHistory.pushState = function($mapping){
	// enable back/forward links:
	$('li.historynav > a').css('display', 'block');
	states.push($mapping);				
}

hxlHistory.back = function(){
	// todo				
}

hxlHistory.foward = function(){
	// todo		
}

// finally, make sure the user doesn' go back through the browser's back button:
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
