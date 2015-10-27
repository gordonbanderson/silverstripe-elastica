/*jslint white: true */
(function($) {
	$(document).ready(function() {
		$('.facetToggle').click(function(e) {
			var jel = $(e.target);

			if (jel.hasClass('rotate')) {
				jel.removeClass('rotate');
				jel.addClass('rotateBack');
				jel.html('&#91;');
			} else {
				jel.removeClass('rotateBack');
				jel.addClass('rotate');
				jel.html('&#93;');
			}

			ul = jel.parent().next();
			//console.log(ul);
			if (ul.hasClass('facetVisible')) {
				ul.removeClass('facetVisible');
				ul.addClass('facetInvisible');
				ul.slideUp(200);
			} else {
				ul.removeClass('facetInvisible');
				ul.addClass('facetVisible');
				ul.slideDown(200);
			}
		});
	});


	$('.autocomplete').autocomplete({
	    serviceUrl: '/autocomplete/search',
	    minChars: 2, //Needs to be more than 1 otherwise no results will be returned due to isBadQuery method,
	    width: '1000px',
	    deferRequestBy: 300,
	    onSelect: function (suggestion) {
	        alert('You selected: ' + suggestion.value + ', ' + suggestion.data);
	    },
	    formatResult: function (suggestion, currentValue) {
	    		    		//console.log('++++ NEW SEARCH AND MATCH ++++');

	    	var marker = ' ZQXVRCTBNYQ ';
	    	//console.log('Suggestion:', suggestion);
	    	//console.log('Current value:', currentValue);
	    	var tokens = currentValue.split(' ');
	    	//console.log('TOKENS', tokens);
	    	// split("(?i)XXX")
	    	// Sort tokens largest first
	    	tokens.sort(function(a, b) { return b.length - a.length; });
	    	var highlightedValue = [marker+suggestion.value+marker];
	    	for (var i = 0; i < tokens.length; i++) {
	    		var nextHighlightedValue = [];
	    		//console.log("---- PROCESSING TOKEN "+tokens[i]+' --------');
	    		for (var j = 0; j < highlightedValue.length; j++) {
	    			var section = highlightedValue[j];
	    			//console.log('SECTION', section);
	    			if (!section.highlighted) {
	    				var token = tokens[i];

			    		var splitter = new RegExp(token, 'ig');
			    		//console.log(splitter);
			    		var splits = section.split(splitter);

			    		var joiner = '<strong>'+token+'</strong>';
			    		joiner.highlighted = true;

			    		for (var k = 0; k < splits.length; k++) {
			    			nextHighlightedValue.push(splits[k]);

			    			// no last item as there is a marker to prevent this
			    			if (k != (splits.length-1)) {
			    				nextHighlightedValue.push(joiner);
			    			}

			    		};

			    		//console.log('Split by token *'+token+'*');
			    		//console.log(splits);

			    		//console.log(highlightedValue);
	    			} else {
	    				nextHighlightedValue.push(section);
	    			}
	    		};

	    		highlightedValue = nextHighlightedValue
	    		//console.log('HIGHLIGHTED IN PROGRESS', highlightedValue);

	    	};
	    	//console.log(highlightedValue);

	    	//console.log('SUGGESTION', suggestion);
	    	//console.log('HIGHLIGHTED', highlightedValue);
	    	var result = highlightedValue.join('');
	    	result = result.replace(marker, '');
	    	result = result.replace(marker, '');
	    	return result.trim();
	    }
	});
})(jQuery);
