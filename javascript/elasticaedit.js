/*jslint white: true */
console.log('el edit');
(function($) {
	$(document).ready(function() {

		var node = $('#Form_EditForm_SiteTreeOnly');
		var originalSiteTreeOnly = node.is(':checked');
		var originalClassesToSearch = $('#Form_EditForm_ClassesToSearch').val();

		node.data('SiteTree', originalSiteTreeOnly);
		node.data('Classes', originalClassesToSearch);

		$('#Form_EditForm_SiteTreeOnly').entwine({
			onchange: function(e) {
				enableOrDisableSiteTreeList();
				showOrHideSearchFields();
			},

			// this is required to correctly show/hide the fields
			// See http://www.silverstripe.org/community/forums/customising-the-cms/show/22067
			onmatch: function(e) {
				enableOrDisableSiteTreeList();
			}
		});

		$('#Form_EditForm_ClassesToSearch').entwine({
			oninput: function(e) {
				showOrHideSearchFields();
			}
		});

		// this is run first time only, to prime the form
		enableOrDisableSiteTreeList();

	});


	function showOrHideSearchFields() {
		var searchFieldsPanel = $('#Form_EditForm_ElasticSearchPageSearchField');
		var searchFieldsIntro = $('#SearchFieldIntro');
		var searchDetailsMessage = $('#SearchFieldsMessage');

		var node = $('#Form_EditForm_SiteTreeOnly');
		var originalSiteTreeOnly = node.data('SiteTree');
		var originalClassesToSearch = node.data('Classes');
		var currentSiteTreeOnly = $('#Form_EditForm_SiteTreeOnly').is(':checked');
		var currentClassesToSearch = $('#Form_EditForm_ClassesToSearch').val();

		if (
			(currentSiteTreeOnly === originalSiteTreeOnly) &&
			(currentClassesToSearch === originalClassesToSearch)
		) {
			searchFieldsPanel.removeClass('hide');
			searchFieldsIntro.removeClass('hide');
			searchDetailsMessage.attr('style','display: none;');
		} else {
			searchFieldsPanel.addClass('hide');
			searchFieldsIntro.addClass('hide');
			searchDetailsMessage.attr('style','display: block;');
		}
	}


	/* Hide the classes to search list when Site Tree Only is selected */
	function enableOrDisableSiteTreeList() {
		var classesField = $('#ClassesToSearch');
		var infoField = $('#SiteTreeOnlyInfo');

		var sel = $('#Form_EditForm_SiteTreeOnly');
		if (sel.is(":checked")) {
			classesField.addClass('hide');
			infoField.addClass('hide');
		} else {
			classesField.removeClass('hide');
			infoField.removeClass('hide');
		}
	}
})(jQuery);
