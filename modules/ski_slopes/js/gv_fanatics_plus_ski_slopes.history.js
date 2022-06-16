(function(jQuery) {
	
	'use strict';
	
	Drupal.behaviors.skiSlopesHistory = {
		attach: function (context) {
			if (jQuery(context).find('.menu-fanatics').length > 0 
			&& jQuery('.my-grandski-ski-slopes-history').hasClass('is-integrant')) {
				jQuery('.navigation__item.integrants').addClass('active');
			}
		}
	};
	
	function insertParam(key, value) {
        key = escape(key); value = escape(value);

        var kvp = document.location.search.substr(1).split('&');
        if (kvp == '') {
            document.location.search = '?' + key + '=' + value;
        }
        else {

            var i = kvp.length; var x; while (i--) {
                x = kvp[i].split('=');

                if (x[0] == key) {
                    x[1] = value;
                    kvp[i] = x.join('=');
                    break;
                }
            }

            if (i < 0) { kvp[kvp.length] = [key, value].join('='); }

            //this will reload the page, it's likely better to store this until finished
            document.location.search = kvp.join('&');
        }
    }
	
	jQuery(document).ready(function($) {
		var previousVal = null;
		$('select[name="season"]').change(function(e) {
			var currentVal = $(this).val();
			if (currentVal == previousVal) {
				return;
			}
			
			previousVal = currentVal;
			//insertParam('season', currentVal);
			var targetUrl = $('option:selected', this).attr('data-url');
			if (!targetUrl) {
				return;
			}
			
			window.location.replace(targetUrl);
		});
	});
	
})(jQuery);
