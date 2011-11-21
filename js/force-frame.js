if(window.jQuery) {
	(function($) {
		if(!window.forceFrameConfig) return;
		
		var cfg = window.forceFrameConfig;
	
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
		var frameUrl = innerLocation.pathname + innerLocation.search;
		if(cfg.useAbsoluteUrl) {
			if(innerLocation.host) {
				frameUrl = innerLocation.host + frameUrl;
				if(innerLocation.protocol) frameUrl = innerLocation.protocol + '//' + frameUrl;
			}
		}
		var parentLocation = window.parent.document.location;
	
		var isParentOnCorrectUrl = parentLocation.href.substring(0, cfg.parentUrl.length) == cfg.parentUrl;
	
		// is this site in an iframe, and is parent on the correct url?
		if(!isParentOnCorrectUrl) {
			// redirect parent to the correct url
			parentLocation.href = buildParentUrl(frameUrl);
		}
		else {
			// extract parent frame url
			var parentFrameUrl = null;
			if(parentLocation.hash && cfg.mode == cfg.modeFragment) {
				parentFrameUrl = unescape(parentLocation.hash.substring(1));
			}
			else if(parentLocation.search && cfg.mode == cfg.modeGet) {
				var regex = new RegExp(escape(cfg.getParam) + '=([^&]*)');
				var matches = regex.exec(parentLocation.search);
				if(matches && matches.length >= 2) {
					parentFrameUrl = unescape(matches[1]);
				}
			}
	
			var parentFirstSeen = !window.parent.__force_frame_check;
			if(parentFirstSeen) window.parent.__force_frame_check = true;
	
			if(!parentFrameUrl || parentFrameUrl != frameUrl) {
				if(parentFirstSeen && parentFrameUrl) {
					if(!cfg.useAbsoluteUrl) {
						if(innerLocation.host) {
							parentFrameUrl = innerLocation.host + parentFrameUrl;
							if(innerLocation.protocol) parentFrameUrl = innerLocation.protocol + '//' + parentFrameUrl;
						}
					}
	
					innerLocation.href = parentFrameUrl;
				}
				else {
					parentLocation.href = buildParentUrl(frameUrl, parentLocation.search);
				}
			}
		}
	})(jQuery);
}