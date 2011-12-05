if(window.jQuery) {
	(function($) {
		var cfgVar = 'ForceFrameChildConfig';
		if(!window[cfgVar]) return;
		
		var cfg = window[cfgVar];
	
		var isEmpty = function(obj) {
			for(var name in obj) {
				return false;
			}
	
			return true;
		};
		
		var parseQueryString = function(queryString) {
			// assume there's a question mark at the beginning
			var params = {};
			if(queryString && queryString.length > 0) {
				var tokens = queryString.substring(1).split('&');
				for(var tokenIndex in tokens) {
					var token = tokens[tokenIndex];
					var name = token;
					var value = '';
					var equalPos = token.indexOf('=');
					if(equalPos !== -1) {
						name = token.substring(0, equalPos);
						value = token.substring(equalPos + 1);
					}
	
					params[unescape(name)] = unescape(value);
				}
			}
	
			return params;
		};
	
		var buildParentUrl = function(frameUrl, parentQueryString) {
			var params = {};
	
			if(cfg.mode == cfg.modeGet) {
				params[cfg.getParam] = frameUrl;
			}
	
			if(parentQueryString) {
				var parentParams = parseQueryString(parentQueryString);
				params = $.extend(parentParams, params);
			}
	
			var completeParentUrl = cfg.parentUrl;
			
			if(!isEmpty(params)) {
				var beforeQueryString = completeParentUrl;
				var queryString = '';
				var questionPos = completeParentUrl.indexOf('?');
				if(questionPos !== -1) {
					beforeQueryString = completeParentUrl.substring(0, questionPos);
					queryString = completeParentUrl.substring(questionPos);
				}
				if(queryString.length > 0) {
					baseParams = parseQueryString(queryString);
					params = $.extend(baseParams, params);
				}
	
				completeParentUrl = beforeQueryString + '?';
				var first = true;
				for(var name in params) {
					if(first) first = false;
					else completeParentUrl += '&';
					completeParentUrl += escape(name) + '=' + escape(params[name]);
				}
			}
	
			if(cfg.mode == cfg.modeFragment) {
				completeParentUrl += '#' + escape(frameUrl);
			}
	
			return completeParentUrl;
		};

		var innerLocation = document.location;
		
		var frameUrl = innerLocation.href;
		if(!cfg.useAbsoluteUrl) frameUrl = frameUrl.substring(cfg.childUrl.length);
		
		try {
			if(window.parent == window) {
				innerLocation.href = buildParentUrl(frameUrl);
			}
			else {
				if(window.parent.forceFrameIntermediateSocket) {
					$(window).load(function() {
						var height = document.body.clientHeight || document.body.offsetHeight || document.body.scrollHeight;
						//window.parent.document.getElementsByTagName('iframe')[0].style.height = (height + 10) + 'px';
						var message = frameUrl + "\n" + height;
						window.parent.forceFrameIntermediateSocket.postMessage(message);
					});
				}
			}
		}
		catch(err)
		{
		}
	})(jQuery);
}