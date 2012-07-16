// Generic object that wraps the history.js API and adds a fallback for non-HTML5 browsers.
var hxlHistory = new Object();

// this is the history.js object we will be using in the background (don't mess with this directly!)
var History = window.History; 

hxlHistory.states = new Array();

// adds a new state to the history, either via history.js (for html5) or with a HXL-specific fallback for HTML4 browsers: 
hxlHistory.pushState = function($data){
	if ( History.enabled ) {
		// that's easy:
		History.pushState($data, null, null); // we don't need a title or new url
	}else{
		// History.js is disabled for this browser - fallback for HTML4 browsers:
		// that's a little harder:
		
		// todo				
	}
}

hxlHistory.back = function(){
	// todo				
}

hxlHistory.foward = function(){
	// todo		
}

// enables a warning message that shows if the user leaves the page in the middle of the mapping process - for HTML4 browsers, if the user accidentially clicks the back button
function enableLeavePageWarning(){
	window.onbeforeunload = function (e) {
	  var message = "You are about to leave this page and lose your current HXL mapping. If you have made a mistake in your mapping process, you can go back to the previous step by clicking the HXL back button at the top left. If you'd prefer to have your browser's back button enabled in HXLator, we recommend using an up-to-date browser that supports the HTML5 history API (any recent browser except Internet Explorer, that is).",
	  e = e || window.event;
	  // For IE and Firefox
	  if (e) {
	    e.returnValue = message;
	  }
	
	  // For Safari
	  return message;
	};
}