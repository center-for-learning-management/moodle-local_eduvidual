window.addEventListener('load', function(){
require(['jquery'], function($) {
	eduvidual_toggle_checked = function(path) {
		var li = $('.' + path);
		li.toggleClass('checked');
		li.find('input').prop('checked', $(li).hasClass('checked'));
		if ($(li).hasClass('checked')) {
			li.find('li').addClass('checked');
			li.find('a.btn-check').html('alles abwählen');
		} else {
			li.find('li').removeClass('checked');
			li.find('a.btn-check').html('alles wählen');
		}

		eduvidual_toggle_shown(path, true, true);
	}

	eduvidual_toggle_shown = function(path, to, children) {
		var li = $('.' + path);
		if (typeof children === 'undefined') children = false;
		if (typeof to !== 'undefined') {
			if (to) li.addClass('shown');
			else li.removeClass('shown');
		} else {
			li.toggleClass('shown');
		}
		if (children) {
			if (li.hasClass('shown'))
				li.find('li').addClass('shown');
			else
				li.find('li').removeClass('shown');
		}
		//li.find('ul').css('display', $(li).hasClass('shown')?'block':'none');
		//li.children('a.btn-show').html($(li).hasClass('shown')?'schließen':'öffnen');
	}

});});
