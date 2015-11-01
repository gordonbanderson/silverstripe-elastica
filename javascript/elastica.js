/*jslint white: true */
(function($) {
	$(document).ready(function() {
		$('#cancelSimilar').click(function(e) {
			var form = $(this).parent().next();
			console.log(form);
			form.find('input.action').removeAttr('disabled');
			var inputField = form.find('input.text');
			inputField.removeAttr('disabled');
			inputField.focus();
			$(this).parent().remove();

			$('div.searchResults').remove();
			$('div#PageNumbers').remove();

		});

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


	/**
	 * Check all of the nodes with data-autocomplete. If they have field and class values, then
	 * instigate autocomplete for this field.
	 */
	$("input[data-autocomplete='true'").each(function(index, inputBox) {
		console.log('auto complete');
		jqInputBox = $(inputBox);
		console.log(jqInputBox);
		var field = jqInputBox.attr('data-autocomplete-field');
		var classes = jqInputBox.attr('data-autocomplete-classes');
		var autoCompleteFn = jqInputBox.attr('data-autocomplete-function');
		var sourceLink = jqInputBox.attr('data-autocomplete-source');
		if (field == null || field == '' || classes == null || classes == '' ||
			autoCompleteFn == null || autoCompleteFn == '') {
			alert('Autocomplete not configured correctly');
		} else {
			jqInputBox.autocomplete({
				serviceUrl: '/autocomplete/search',
				preventBadQueries: false,
			    minChars: 2, //Needs to be more than 1 otherwise no results will be returned due to isBadQuery method,
			    width: '1000px',
			    deferRequestBy: 250,
			    params: {'field': field, 'classes': classes},
			    onSelect: function (suggestion) {
			        if (autoCompleteFn == 'GOTO') {
			        	var link = suggestion.data['Link'];
			        	window.location.href = link;
			        } else if (autoCompleteFn == 'SIMILAR') {
			        	var link = sourceLink;
			        	link = link + 'similar/'+suggestion.data['Class']+'/'+suggestion.data['ID'];
			        	window.location.href = link;
			        } else  if (autoCompleteFn == 'SEARCH') {
			        	// text is already set, find search button and click
			        	var searchForm = jqInputBox.parent().parent().parent().parent();
			        	searchForm.submit();
			        }
			    },
			    formatResult: function (suggestion, currentValue) {
	    		   	//console.log('++++ NEW SEARCH AND MATCH ++++');

			    	var marker = ' ZQXVRCTBNYQ ';
			    	console.log('Suggestion:', suggestion);
			    	console.log('Current value:', currentValue);
			    	var tokens = currentValue.trim().split(' ');
			    	//console.log('TOKENS', tokens);
			    	// split("(?i)XXX")
			    	// Sort tokens largest first
			    	tokens.sort(function(a, b) { return b.length - a.length; });

			    	console.log('Tokens', tokens);

			    	suggestionText = suggestion.value.trim();
			    	var highlightedValue = [marker+suggestionText+marker];
			    	for (var i = 0; i < tokens.length; i++) {
			    		var nextHighlightedValue = [];
			    		console.log("---- PROCESSING TOKEN "+tokens[i]+' --------');
			    		for (var j = 0; j < highlightedValue.length; j++) {
			    			var section = highlightedValue[j];
			    			console.log('SECTION', section);
			    			if (!section.highlighted) {
			    				var token = tokens[i];

					    		var splitter = new RegExp(token, 'ig');
					    		console.log(splitter);
					    		var splits = section.split(splitter);

					    		console.log('SPLITS', splits);

/*
					    		var tokenPos = section.indexOf(token);
					    		var tokenLen = token.length;
					    		if (tokenPos != -1) {
					    			var origToken = section.substr(tokenPos, tokenLen);
					    			console.log('ORIG TOKEN:', origToken);
					    		}
					    		console.log('TOKEN POS', tokenPos);
*/



					    		var lenCtr = 0;
					    		for (var k = 0; k < splits.length; k++) {
					    			nextHighlightedValue.push(splits[k]);
					    			lenCtr += splits[k].length;
					    			console.log('LEN CTR', lenCtr);
					    			// no last item as there is a marker to prevent this
					    			if (k != (splits.length-1)) {
					    				originalToken = section.substr(lenCtr, token.length);
					    				lenCtr += token.length;
					    				joiner = '<strong>'+originalToken+'</strong>';
					    									    		joiner.highlighted = true;
console.log('ORIGINAL TOKEN', originalToken);

					    				nextHighlightedValue.push(joiner);
					    			}
					    		};

					    		console.log('Split by token *'+token+'*');
					    		console.log(splits);

					    		console.log(highlightedValue);
			    			} else {
			    				nextHighlightedValue.push(section);
			    			}
			    		};

			    		highlightedValue = nextHighlightedValue
			    		console.log('HIGHLIGHTED IN PROGRESS', highlightedValue);

			    	};
			    	console.log(highlightedValue);

			    	console.log('SUGGESTION', suggestion);
			    	console.log('HIGHLIGHTED', highlightedValue);
			    	var result = highlightedValue.join('');
			    	result = result.replace(marker, '');
			    	result = result.replace(marker, '');
			    	return result.trim();
			    }
			})
		}
	});

})(jQuery);
