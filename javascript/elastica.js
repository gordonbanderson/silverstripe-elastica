/*jslint white: true */
(function($) {
	$(document).ready(function() {
		$('#cancelSimilar').click(function(e) {
			var form = $(this).parent().next();
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
		jqInputBox = $(inputBox);
		console.log(jqInputBox);
		var field = jqInputBox.attr('data-autocomplete-field');
		var classes = jqInputBox.attr('data-autocomplete-classes');
		var sitetree = jqInputBox.attr('data-autocomplete-sitetree');
		var autoCompleteFn = jqInputBox.attr('data-autocomplete-function');
		var sourceLink = jqInputBox.attr('data-autocomplete-source');
		if (field === null || field === '' || classes === null || classes === '' ||
			autoCompleteFn === null || autoCompleteFn === '' ||
			sitetree === null || sitetree ==='') {
			alert('Autocomplete not configured correctly');
		} else {
			if (sitetree) {
				classes = '';
			}

			jqInputBox.autocomplete({
				serviceUrl: '/autocomplete/search',
				preventBadQueries: false, // >1 char needed for results
				minChars: 2,
				width: '1000px',
				deferRequestBy: 250,
				params: {
					'field': field,
					'classes': classes,
					'filter': sitetree
				},
				onSelect: function(suggestion) {
					if (autoCompleteFn == 'GOTO') {
						var link = suggestion.data.Link;
						window.location.href = link;
					} else if (autoCompleteFn == 'SIMILAR') {
						var link = sourceLink+'similar/' + suggestion.data.Class + '/' + suggestion.data.ID;
						window.location.href = link;
					} else if (autoCompleteFn == 'SEARCH') {
						// text is already set, find search button and click
						var searchForm = jqInputBox.parent().parent().parent().parent();
						searchForm.submit();
					}
				},
				formatResult: function(suggestion, currentValue) {
					var marker = ' ZQXVRCTBNYQ ';
					var tokens = currentValue.trim().split(' ');
					//console.log('TOKENS', tokens);
					// split("(?i)XXX")
					// Sort tokens largest first
					tokens.sort(function(a, b) {
						return b.length - a.length;
					});

					suggestionText = suggestion.value.trim();
					var highlightedValue = [marker + suggestionText + marker];
					for (var i = 0; i < tokens.length; i++) {
						var nextHighlightedValue = [];
						for (var j = 0; j < highlightedValue.length; j++) {
							var section = highlightedValue[j];
							if (!section.highlighted) {
								var token = tokens[i];
								var splitter = new RegExp(token, 'ig');
								var splits = section.split(splitter);

								var lenCtr = 0;
								for (var k = 0; k < splits.length; k++) {
									nextHighlightedValue.push(splits[k]);
									lenCtr += splits[k].length;
									// no last item as there is a marker to prevent this
									if (k != (splits.length - 1)) {
										originalToken = section.substr(lenCtr, token.length);
										lenCtr += token.length;
										joiner = '<strong>' + originalToken + '</strong>';
										joiner.highlighted = true;
										nextHighlightedValue.push(joiner);
									}
								}

							} else {
								nextHighlightedValue.push(section);
							}
						}

						highlightedValue = nextHighlightedValue;
					}

					var result = highlightedValue.join('');
					result = result.replace(marker, '');
					result = result.replace(marker, '');
					return result.trim();
				}
			});
		}
	});

})(jQuery);
