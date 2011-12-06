(function() {
	var configVar = 'ForceFrameParentConfig';
	
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
	
	var getWindowScroll = function() {
		var scrOfX = 0, scrOfY = 0;
		if( typeof( window.pageYOffset ) == 'number' ) {
			//Netscape compliant
			scrOfY = window.pageYOffset;
			scrOfX = window.pageXOffset;
		} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
			//DOM compliant
			scrOfY = document.body.scrollTop;
			scrOfX = document.body.scrollLeft;
		} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
		    //IE6 standards compliant mode
		    scrOfY = document.documentElement.scrollTop;
		    scrOfX = document.documentElement.scrollLeft;
		}
		return [ scrOfX, scrOfY ];
	};
	
	var scrollToEl = function(el) {
		var windowScroll = getWindowScroll();
		
		var elTop = 0;
		if(el.offsetParent) {
			do {
				elTop += el.offsetTop;
			} while (el = el.offsetParent);
		}
		
		if(windowScroll[1] > elTop) window.scroll(windowScroll[0], elTop);
	}
	
	if(window.easyXDM && window[configVar]) {
		var easyXDM = window.easyXDM.noConflict('force-frame');
		var cfg = window[configVar];
		
		// extract parent frame url
		var parentLocation = document.location;
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
		
		var frameUrl = '';
		if(!parentFrameUrl || !cfg.useAbsoluteUrl) {
			frameUrl += cfg.childUrl;
		}
		if(parentFrameUrl) {
			frameUrl += parentFrameUrl;
		}
		
		// find the current script
		var el = null;
		var scripts = document.getElementsByTagName('script');
		for(var scriptIndex in scripts) {
			var script = scripts[scriptIndex];
			if(script && script.src && script.src == cfg.parentJsUrl) {
				el = script.parentNode;
				break;
			}
		}
		
		if(el) {
			var props = cfg.iframeAttributes || {};
			var socket = new easyXDM.Socket({
				remote: cfg.pluginUrl + 'intermediate.html?url=' + encodeURIComponent(frameUrl),
				swf: cfg.pluginUrl + 'js/easyxdm/easyxdm.swf',
				container: el,
				props: props,
				onMessage: function(message, origin) {
					var tokens = message.split("\n");
					var newFrameUrl = tokens[0];
					var newFrameHeight = parseInt(tokens[1]);
					var frame = this.container.getElementsByTagName("iframe")[0];
					frame.style.height = (newFrameHeight + 100) + "px";
					if(cfg.autoScroll) scrollToEl(frame);
					if(newFrameUrl != parentFrameUrl) {
						parentFrameUrl = newFrameUrl;
						if(cfg.mode == cfg.modeFragment) {
							parentLocation.hash = escape(parentFrameUrl);
						}
						else if(cfg.mode == cfg.modeGet) {
							var params = parseQueryString(parentLocation.search);
							params[cfg.getParam] = parentFrameUrl;
							var newSearch = '';
							var first = true;
							for(var paramName in params) {
								if(first) {
									newSearch += '?';
									first = false;
								}
								else newSearch += '&';
								newSearch += paramName + '=' + escape(params[paramName]);
							}
							parentLocation.search = newSearch;
						}
					}
				}
			});
		}
	}
})();