<?php
/*
Plugin Name: Force Frame
Description: A plugin that forces the Wordpress site as an iframe into a configurable parent site
Version: 1.0
Author: Lorenzo Carrara <lorenzo.carrara@cubica.eu>
Author URI: http://www.cubica.eu
*/

define('FORCE_FRAME_VERSION', '1.0');
define('FORCE_FRAME_TEXT_DOMAIN', 'force_frame');

class ForceFrameAdmin {
	const SETTINGS_GROUP = 'force_frame_settings';
	const SETTINGS_SECTION = 'force_frame_settings';
	const PARENT_URL_OPTION_NAME = 'force_frame_parent_url';
	const MODE_OPTION_NAME = 'force_frame_mode';
	const GET_PARAM_OPTION_NAME = 'force_frame_get_param';
	const USE_ABSOLUTE_URL_OPTION_NAME = 'force_frame_use_absolute_url';
	const DEFAULT_GET_PARAM = 'frame';
	const DEFAULT_USE_ABSOLUTE_URL = 0;
	const DEFAULT_MODE = ForceFrame::MODE_FRAGMENT;
	const SCRIPTS_HOOK_NAME = 'settings_page_force-frame/force-frame';

	public static function init() {
		add_action('admin_menu', array(__CLASS__, 'admin_menu'));
	}

	public static function admin_menu() {
		// create new options page
		add_options_page(__('Force Frame', FORCE_FRAME_TEXT_DOMAIN), __('Force Frame', FORCE_FRAME_TEXT_DOMAIN), 'administrator', __FILE__, array(__CLASS__, 'create_options_page'));

		// call register settings function
		add_action( 'admin_init', array(__CLASS__, 'register_settings') );
	}

	public static function create_options_page() {
		?>
<div class="wrap">
	<div class="icon32" id="icon-options-general"><br /></div>
	<h2><?php echo __('Force Frame', FORCE_FRAME_TEXT_DOMAIN); ?></h2>
	<form method="post" action="options.php">
	    <?php settings_fields( self::SETTINGS_GROUP ); ?>
	    <?php do_settings_sections( __FILE__ ); ?>
	    <p class="submit">
	    	<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', FORCE_FRAME_TEXT_DOMAIN); ?>" />
	    </p>
	</form>
</div>
		<?php
	}
	
	public static function register_settings() {
		register_setting(self::SETTINGS_GROUP, self::PARENT_URL_OPTION_NAME);
		register_setting(self::SETTINGS_GROUP, self::USE_ABSOLUTE_URL_OPTION_NAME);
		register_setting(self::SETTINGS_GROUP, self::MODE_OPTION_NAME, array(__CLASS__, 'sanitize_mode_setting'));
		register_setting(self::SETTINGS_GROUP, self::GET_PARAM_OPTION_NAME);
		add_settings_section(self::SETTINGS_SECTION, __('Force frame configuration', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'settings_section_text'), __FILE__);
		add_settings_field(self::PARENT_URL_OPTION_NAME, __('Parent URL', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_parent_url_settings_fields'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::USE_ABSOLUTE_URL_OPTION_NAME, __('Use absolute URL', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_use_absolute_url_settings_field'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::MODE_OPTION_NAME, __('Mode', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_mode_settings_field'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::GET_PARAM_OPTION_NAME, __('GET parameter name', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_get_parameter_settings_field'), __FILE__, self::SETTINGS_SECTION);
	}
	
	public static function settings_section_text() {
		?>
<p><?php echo sprintf(__('In order to force this Wordpress site inside a frame or iframe, the <strong>Parent URL</strong> must point to a web page that contains a frame or iframe pointing to <a href="%s">the homepage of this site</a>. This plugin will propagate the correct URL of the iframe to the URL of the parent window; this way, if the parent URL is shared among users, the iframe will load the correct page instead of the homepage.', FORCE_FRAME_TEXT_DOMAIN), get_bloginfo('url')); ?></p>
<p><?php echo __('This plugin can work in two distinct modes:', FORCE_FRAME_TEXT_DOMAIN); ?></p>
<ul style="list-style: disc; padding-left: 2.5em;">
	<li><strong><?php echo __('Fragment mode', FORCE_FRAME_TEXT_DOMAIN); ?></strong>: <?php echo __('the correct url of the frame will be propagated using the fragment part of the parent window\'s URL', FORCE_FRAME_TEXT_DOMAIN); ?></li>
	<li><strong><?php echo __('GET mode', FORCE_FRAME_TEXT_DOMAIN); ?></strong>: <?php echo __('the correct url of the frame will be propagated in a GET parameter appended to the parent window\'s URL', FORCE_FRAME_TEXT_DOMAIN); ?></li>
</ul>
<p><?php echo __('<strong>Fragment mode</strong> is preferable because the parent site will never reload, but is not applicable if the parent site needs to use the fragment part of its URL for other purposes; in that case, <strong>GET mode</strong> is a forced choice.'); ?></p>
<p><?php echo __('If you choose or need to use <strong>GET mode</strong>, you can customize the <strong>GET parameter name</strong> in order to avoid conflicts with other known parameters.'); ?></p>
<p><?php echo __('Finally, you can choose to propagate the absolute URL of the iframe instead of the relative path, using the <strong>Use absolute URL</strong> setting.'); ?></p>
		<?php
	}
	
	public static function sanitize_mode_setting($value) {
		if(!in_array($value, array(
			ForceFrame::MODE_FRAGMENT,
			ForceFrame::MODE_GET
		))) {
			add_settings_error(self::MODE_OPTION_NAME, 'force_frame_invalid_mode', __('Invalid mode', FORCE_FRAME_TEXT_DOMAIN));
			$value = self::get_mode();
		}
		
		return $value;
	}
	
	public static function create_parent_url_settings_fields() {
		echo '<input type="text" maxlength="255" id="' . self::PARENT_URL_OPTION_NAME . '" name="' . self::PARENT_URL_OPTION_NAME . '" value="' . esc_attr(self::get_parent_url()) . '" />';
	}
	
	public static function create_get_parameter_settings_field() {
		echo '<input type="text" maxlength="255" id="' . self::GET_PARAM_OPTION_NAME . '" name="' . self::GET_PARAM_OPTION_NAME . '" value="' . esc_attr(self::get_get_param()) . '" />';
	}
	
	public static function create_use_absolute_url_settings_field() {
		echo '<input type="checkbox" id="' . self::USE_ABSOLUTE_URL_OPTION_NAME . '" name="' . self::USE_ABSOLUTE_URL_OPTION_NAME . '" value="1"' . (self::get_use_absolute_url()?' checked="checked"':'') . ' />';
	}
	
	public static function create_mode_settings_field() {
		$value = self::get_mode();
		$output = '';
		$output .= '<input type="radio" name="' . self::MODE_OPTION_NAME . '" id="' . self::MODE_OPTION_NAME . '_' . ForceFrame::MODE_FRAGMENT . '" value="' . ForceFrame::MODE_FRAGMENT . '"' . (($value == ForceFrame::MODE_FRAGMENT)?' checked="checked"':'') . ' />';
		$output .= '&nbsp;<label for="' . self::MODE_OPTION_NAME . '_' . ForceFrame::MODE_FRAGMENT . '">' . __('Fragment', FORCE_FRAME_TEXT_DOMAIN) . '</label><br/>';
		$output .= '<input type="radio" name="' . self::MODE_OPTION_NAME . '" id="' . self::MODE_OPTION_NAME . '_' . ForceFrame::MODE_GET . '" value="' . ForceFrame::MODE_GET . '"' . (($value == ForceFrame::MODE_GET)?' checked="checked"':'') . ' />';
		$output .= '&nbsp;<label for="' . self::MODE_OPTION_NAME . '_' . ForceFrame::MODE_GET . '">' . __('GET', FORCE_FRAME_TEXT_DOMAIN) . '</label>';
		echo $output;
	}
	
	public static function get_parent_url() {
		return get_option(self::PARENT_URL_OPTION_NAME, '');
	}
	
	public static function get_get_param() {
		return get_option(self::GET_PARAM_OPTION_NAME, self::DEFAULT_GET_PARAM);
	}
	
	public static function get_use_absolute_url() {
		$value = get_option(self::USE_ABSOLUTE_URL_OPTION_NAME, self::DEFAULT_USE_ABSOLUTE_URL);
		return !empty($value);
	}
	
	public static function get_mode() {
		return get_option(self::MODE_OPTION_NAME, self::DEFAULT_MODE);
	}
}

class ForceFrame {
	const MODE_GET = 'get';
	const MODE_FRAGMENT = 'fragment';
	
	public static function init() {
		add_action('wp_head', array(__CLASS__, 'wp_head'));
	}
	
	public static function wp_head() {
		// check if we have a parent url
		$parentUrl = ForceFrameAdmin::get_parent_url();
		$getParam = ForceFrameAdmin::get_get_param();
		if(!empty($parentUrl)) {
?>
<script type="text/javascript">
(function() {
	// configs
	var parentUrl = '<?php echo $parentUrl; ?>';
	var getParam = '<?php echo ForceFrameAdmin::get_get_param(); ?>';
	var useAbsoluteUrl = <?php echo ForceFrameAdmin::get_use_absolute_url()?'true':'false'?>;
	var mode = '<?php echo ForceFrameAdmin::get_mode(); ?>';
	var modeFragment = '<?php echo ForceFrame::MODE_FRAGMENT; ?>';
	var modeGet = '<?php echo ForceFrame::MODE_GET; ?>';
		
	var innerLocation = document.location;
	var frameUrl = innerLocation.pathname + innerLocation.search;
	if(useAbsoluteUrl) {
		if(innerLocation.host) {
			frameUrl = innerLocation.host + frameUrl;
			if(innerLocation.protocol) frameUrl = innerLocation.protocol + '//' + frameUrl;
		}
	}
	var parentLocation = window.parent.document.location;

	if(parentLocation.href.substring(0, parentUrl.length) != parentUrl || window.parent.__force_frame_check) {
		var correctParentUrl = parentUrl + parentLocation.search;
		if(mode == modeFragment) {
			correctParentUrl += '#' + escape(frameUrl);
		}
		else if(mode == modeGet) {
			var getAssignment = escape(getParam) + '=' + escape(frameUrl);
			var regex = new RegExp(getParam + '=([^&]*)', 'g');
			if(correctParentUrl.match(regex)) correctParentUrl = correctParentUrl.replace(regex, getAssignment);
			else {
				var separator = (correctParentUrl.indexOf('?') === -1)?'?':'&';
				correctParentUrl += separator + getAssignment;
			}
		}

		if(parentLocation.href != correctParentUrl) {
			window.parent.document.location.href = correctParentUrl;
		}
	}
	else {
		window.parent.__force_frame_check = true;
		var requiredFrameUrl = null;
		if(parentLocation.hash && mode == modeFragment) {
			requiredFrameUrl = unescape(parentLocation.hash.substring(1));
		}
		else if(parentLocation.search && mode == modeGet) {
			var regex = new RegExp(escape(getParam) + '=([^&]*)');
			var matches = regex.exec(parentLocation.search);
			if(matches && matches.length >= 2) {
				requiredFrameUrl = unescape(matches[1]);
			}
		}
		
		if(requiredFrameUrl && frameUrl != requiredFrameUrl) {
			if(!useAbsoluteUrl) {
				if(innerLocation.host) {
					requiredFrameUrl = innerLocation.host + requiredFrameUrl;
					if(innerLocation.protocol) requiredFrameUrl = innerLocation.protocol + '//' + requiredFrameUrl;
				}
			}

			innerLocation.href = requiredFrameUrl;
		}
	}
})();
</script>
<?php
		}
	}
}

// init plugin
function force_frame_init() {
	ForceFrame::init();
	ForceFrameAdmin::init();	
}

add_action('init', 'force_frame_init');