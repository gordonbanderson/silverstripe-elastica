/*jslint white: true */
console.log('el edit');
(function($) {
	$(document).ready(function() {

		$('#Form_EditForm_SiteTreeOnly').entwine({
			onchange: function(e) {
				enableOrDisableSiteTreeList();
			},

			// this is required to correctly show/hide the fields
			// See http://www.silverstripe.org/community/forums/customising-the-cms/show/22067
			onmatch: function(e) {
				enableOrDisableSiteTreeList();
			}
		});

		// this is run first time only, to prime the form
		enableOrDisableSiteTreeList();

	});

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
