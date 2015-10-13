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
			console.log(ul);
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
})(jQuery);
