
function check_option(id, card, type) {    
    jQuery("#payment_option_" + type).val(id);
    jQuery("#payment_mean_brand_" + type).val(card);
    jQuery.cookie('payment_mean_brand_'+type, card);
    
    if (card === 'BOL') {
        event.preventDefault();
        jQuery('#axepta_selecte_payment').html('Security Social Number :');
        jQuery('#computop_one_time_cards').html('<input type="text" id="social_security_number" name="social_security_number" value="" required="required" style="width:100%;" placeholder="000.000.000-00" />');
    }
    
}

function check_option_sdd(id, type) {    
    jQuery("#payment_mean_brand_" + type).val(id);
}

function computop_customer_onclick() {
    if(jQuery("#register_payment").val() === 'no')
    {
        jQuery("#register_payment").val('yes');
    } else {
        jQuery("#register_payment").val('no');
    }
}

jQuery(document).ready(function () {
    
    (function (factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD (Register as an anonymous module)
		define(['jquery'], factory);
	} else if (typeof exports === 'object') {
		// Node/CommonJS
		module.exports = factory(require('jquery'));
	} else {
		// Browser globals
		factory(jQuery);
	}
}(function (jQuery) {

	var pluses = /\+/g;

	function encode(s) {
		return config.raw ? s : encodeURIComponent(s);
	}

	function decode(s) {
		return config.raw ? s : decodeURIComponent(s);
	}

	function stringifyCookieValue(value) {
		return encode(config.json ? JSON.stringify(value) : String(value));
	}

	function parseCookieValue(s) {
		if (s.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape...
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}

		try {
			// Replace server-side written pluses with spaces.
			// If we can't decode the cookie, ignore it, it's unusable.
			// If we can't parse the cookie, ignore it, it's unusable.
			s = decodeURIComponent(s.replace(pluses, ' '));
			return config.json ? JSON.parse(s) : s;
		} catch(e) {}
	}

	function read(s, converter) {
		var value = config.raw ? s : parseCookieValue(s);
		return jQuery.isFunction(converter) ? converter(value) : value;
	}

	var config = jQuery.cookie = function (key, value, options) {

		// Write

		if (arguments.length > 1 && !jQuery.isFunction(value)) {
			options = jQuery.extend({}, config.defaults, options);

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setMilliseconds(t.getMilliseconds() + days * 864e+5);
			}

			return (document.cookie = [
				encode(key), '=', stringifyCookieValue(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// Read

		var result = key ? undefined : {},
			// To prevent the for loop in the first place assign an empty array
			// in case there are no cookies at all. Also prevents odd result when
			// calling $.cookie().
			cookies = document.cookie ? document.cookie.split('; ') : [],
			i = 0,
			l = cookies.length;

		for (; i < l; i++) {
			var parts = cookies[i].split('='),
				name = decode(parts.shift()),
				cookie = parts.join('=');

			if (key === name) {
				// If second argument (value) is a function it's a converter...
				result = read(cookie, value);
				break;
			}

			// Prevent storing a cookie that we couldn't decode.
			if (!key && (cookie = read(cookie)) !== undefined) {
				result[name] = cookie;
			}
		}

		return result;
	};

	config.defaults = {};

	jQuery.removeCookie = function (key, options) {
		// Must not alter options, thus extending a fresh object...
		jQuery.cookie(key, '', jQuery.extend({}, options, { expires: -1 }));
		return !jQuery.cookie(key);
	};

}));


    jQuery('#stop_recurring_button').click(function (e) {
        e.preventDefault();
        jQuery('.stop_recurring_confirmation, .computop-overlay').show();
    });
    
    jQuery('#confirm_stop_recurring').click(function (e) {
        jQuery('#computop_stop_recurring_form').submit();
    });
    jQuery('#noconfirm_stop_recurring').click(function (e) {
        jQuery('.stop_recurring_confirmation, .computop-overlay').hide();
    });

    jQuery(document).mouseup(function (e) {
        var container = jQuery(".stop_recurring_confirmation");
        if (!container.is(e.target) && container.has(e.target).length === 0){
            jQuery('#noconfirm_stop_recurring').click();
        }
    });
});
