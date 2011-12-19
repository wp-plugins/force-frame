<?php
/*
Plugin Name: Force Frame
Description: A plugin that forces the Wordpress site as an iframe into a configurable parent site, with cross-domain support
Version: 1.2.1
Author: Lorenzo Carrara <lorenzo.carrara@cubica.eu>
Author URI: http://www.cubica.eu
*/

define('FORCE_FRAME_VERSION', '1.2.1');
define('FORCE_FRAME_TEXT_DOMAIN', 'force_frame');

class ForceFrameAdmin {
	const SETTINGS_GROUP = 'force_frame_settings';
	const SETTINGS_SECTION = 'force_frame_settings';
	const PARENT_URL_OPTION_NAME = 'force_frame_parent_url';
	const MODE_OPTION_NAME = 'force_frame_mode';
	const GET_PARAM_OPTION_NAME = 'force_frame_get_param';
	const USE_ABSOLUTE_URL_OPTION_NAME = 'force_frame_use_absolute_url';
	const AUTO_SCROLL_OPTION_NAME = 'force_frame_auto_scroll';
	const AUTO_ADJUST_HEIGHT_OPTION_NAME = 'force_frame_auto_adjust_height';
	const IFRAME_ATTRIBUTES_OPTION_NAME = 'force_frame_iframe_attributes';
	const WHITELISTED_IPS_OPTION_NAME = 'force_frame_whitelisted_ips';
	const DEFAULT_GET_PARAM = 'frame';
	const DEFAULT_USE_ABSOLUTE_URL = 0;
	const DEFAULT_MODE = ForceFrame::MODE_FRAGMENT;
	const DEFAULT_AUTO_SCROLL = 1;
	const DEFAULT_AUTO_ADJUST_HEIGHT = 0;
	const DEFAULT_IFRAME_ATTRIBUTES = 'width=100%';
	const SCRIPTS_HOOK_NAME = 'settings_page_force-frame/force-frame';

	public static function init() {
		add_action('admin_menu', array(__CLASS__, 'admin_menu'));
		$parentUrl = self::get_parent_url();
		if(empty($parentUrl)) add_action('admin_notices', array(__CLASS__, 'activation_notice'));
	}
	
	public static function activation_notice() {
		?>
<div class="updated">
	<p><?php echo sprintf(__('%1$s is not configured: go to the <a href="%2$s">settings page</a> and follow the instructions.', FORCE_FRAME_TEXT_DOMAIN), '<strong>Force Frame</strong>', 'options-general.php?page=force-frame/' . basename(__FILE__)); ?></p>
</div>
		<?php
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
		register_setting(self::SETTINGS_GROUP, self::AUTO_SCROLL_OPTION_NAME);
		register_setting(self::SETTINGS_GROUP, self::AUTO_ADJUST_HEIGHT_OPTION_NAME);
		register_setting(self::SETTINGS_GROUP, self::IFRAME_ATTRIBUTES_OPTION_NAME);
		register_setting(self::SETTINGS_GROUP, self::WHITELISTED_IPS_OPTION_NAME, array(__CLASS__, 'sanitize_whitelisted_ips_setting'));
		add_settings_section(self::SETTINGS_SECTION, __('Force frame configuration', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'settings_section_text'), __FILE__);
		add_settings_field(self::PARENT_URL_OPTION_NAME, __('Parent URL', FORCE_FRAME_TEXT_DOMAIN) . '&nbsp;<strong>*</strong>', array(__CLASS__, 'create_parent_url_settings_fields'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::USE_ABSOLUTE_URL_OPTION_NAME, __('Use absolute URL', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_use_absolute_url_settings_field'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::MODE_OPTION_NAME, __('Mode', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_mode_settings_field'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::GET_PARAM_OPTION_NAME, __('GET parameter name', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_get_parameter_settings_field'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::AUTO_SCROLL_OPTION_NAME, __('Auto scroll', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_auto_scroll_settings_field'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::AUTO_ADJUST_HEIGHT_OPTION_NAME, __('Auto adjust height', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_auto_adjust_height_settings_field'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::IFRAME_ATTRIBUTES_OPTION_NAME, __('IFrame Attributes', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_iframe_attributes_settings_field'), __FILE__, self::SETTINGS_SECTION);
		add_settings_field(self::WHITELISTED_IPS_OPTION_NAME, __('Whitelisted IPs', FORCE_FRAME_TEXT_DOMAIN), array(__CLASS__, 'create_whitelisted_ips_settings_field'), __FILE__, self::SETTINGS_SECTION);
	}
	
	public static function settings_section_text() {
		?>
<p><?php echo __('In order to force this Wordpress site inside an iframe on another site, the following steps are required:', FORCE_FRAME_TEXT_DOMAIN); ?></p>
<ol>
	<li>
		<?php echo __('Fill the <strong>Parent URL</strong> field below with the URL of the web page where the iframe should be created, for example:', FORCE_FRAME_TEXT_DOMAIN); ?>
		<p>
			<pre><?php echo esc_html('http://www.example.com/parent-page.php'); ?></pre>
		</p>
	</li>
	<li>
		<?php echo __('Add the following snippet inside the HTML of the parent page, in the spot where you want the iframe to be created:', FORCE_FRAME_TEXT_DOMAIN); ?>
		<p>
			<pre><?php echo esc_html('<script type="text/javascript" src="' . esc_attr(ForceFrame::getParentJsUrl()) . '"></script>'); ?></pre>
		</p>
	</li>
</ol>
<p><?php echo __('You\'re done!', FORCE_FRAME_TEXT_DOMAIN); ?></p>
<p><?php echo __('Beside showing your site inside an iframe, this plugin does the following:', FORCE_FRAME_TEXT_DOMAIN); ?></p>
<ul style="list-style: disc; padding-left: 2.5em;">
	<li><?php echo sprintf(__('when the iframe is loaded, and each time the user navigates to a different url in your site inside the iframe, force-frame communicates the URL of your site to the parent site using %1$s;', FORCE_FRAME_TEXT_DOMAIN), '<a href="http://easyxdm.net" target="_blank">easyXDM</a>'); ?></li>
	<li><?php echo __('the parent site then copies that URL inside the fragment or a GET parameter of its URL;', FORCE_FRAME_TEXT_DOMAIN); ?></li>
	<li><?php echo __('this way, if the user shares the parent site\'s URL with someone else, it will contain a reference to the correct URL of your site;', FORCE_FRAME_TEXT_DOMAIN); ?></li>
	<li><?php echo __('force-frame will use that reference in order to load the correct page of your site inside the iframe.', FORCE_FRAME_TEXT_DOMAIN); ?></li>
</ul>
<p><?php echo __('Additional options are available: see each setting\'s description for further details.', FORCE_FRAME_TEXT_DOMAIN); ?></p>
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
	
	public static function sanitize_whitelisted_ips_setting($value) {
		$rawIPs = explode("\n", $value);
		$ips = array();
		foreach($rawIPs as $rawIP) {
			$ip = trim(str_replace("\r", "", $rawIP));
			if(!empty($ip)) {
				if(!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/', $ip)) {
					add_settings_error(self::WHITELISTED_IPS_OPTION_NAME, 'force_frame_invalid_whitelisted_ips', __('Whitelisted IPs are not in the right format', FORCE_FRAME_TEXT_DOMAIN));
					$ips = self::get_whitelisted_ips();
					break;
				}
				
				$ips[] = $ip;
			}
		}
		
		return implode("\n", $ips);
	}
		
	
	public static function create_parent_url_settings_fields() {
		echo '<input type="text" maxlength="255" id="' . self::PARENT_URL_OPTION_NAME . '" name="' . self::PARENT_URL_OPTION_NAME . '" value="' . esc_attr(self::get_parent_url()) . '" style="width: 400px;" />';
	}
	
	public static function create_get_parameter_settings_field() {
		$output = '';
		$output .= '<input type="text" maxlength="255" id="' . self::GET_PARAM_OPTION_NAME . '" name="' . self::GET_PARAM_OPTION_NAME . '" value="' . esc_attr(self::get_get_param()) . '" />';
		$output .= '<p class="description">';
		$output .= __('If you\'re using GET mode, here you can customize the GET parameter name used by force-frame.', FORCE_FRAME_TEXT_DOMAIN);
		$output .= '</p>';
		echo $output;
	}
	
	public static function create_use_absolute_url_settings_field() {
		$output = '';
		$output .= '<input type="checkbox" id="' . self::USE_ABSOLUTE_URL_OPTION_NAME . '" name="' . self::USE_ABSOLUTE_URL_OPTION_NAME . '" value="1"' . (self::get_use_absolute_url()?' checked="checked"':'') . ' />';
		$output .= '<p class="description">';
		$output .= __('By default, force-frame will just propagate the path part of your site\'s URL to the parent; if you want the absolute URL to be propagated, enable this option.', FORCE_FRAME_TEXT_DOMAIN);
		$output .= '</p>';
		echo $output;
	}
	
	public static function create_mode_settings_field() {
		$value = self::get_mode();
		$output = '';
		$output .= '<input type="radio" name="' . self::MODE_OPTION_NAME . '" id="' . self::MODE_OPTION_NAME . '_' . ForceFrame::MODE_FRAGMENT . '" value="' . ForceFrame::MODE_FRAGMENT . '"' . (($value == ForceFrame::MODE_FRAGMENT)?' checked="checked"':'') . ' />';
		$output .= '&nbsp;<label for="' . self::MODE_OPTION_NAME . '_' . ForceFrame::MODE_FRAGMENT . '">' . __('Fragment', FORCE_FRAME_TEXT_DOMAIN) . '</label><br/>';
		$output .= '<input type="radio" name="' . self::MODE_OPTION_NAME . '" id="' . self::MODE_OPTION_NAME . '_' . ForceFrame::MODE_GET . '" value="' . ForceFrame::MODE_GET . '"' . (($value == ForceFrame::MODE_GET)?' checked="checked"':'') . ' />';
		$output .= '&nbsp;<label for="' . self::MODE_OPTION_NAME . '_' . ForceFrame::MODE_GET . '">' . __('GET', FORCE_FRAME_TEXT_DOMAIN) . '</label>';
		$output .= '<p class="description">';
		$output .= __('By default, force-frame will use the fragment part of the parent\'s URL to propagate the URL of your site; however, if the parent site must use its fragment for other purposes, force-frame can use a configurable GET parameter instead. Fragment mode is generally preferred because the parent doesn\'t reload each time the user navigates to a different URL in the iframe, while it does in GET mode.', FORCE_FRAME_TEXT_DOMAIN);
		$output .= '</p>';
		echo $output;
	}
	
	public static function create_auto_scroll_settings_field() {
		$output = '';
		$output .= '<input type="checkbox" id="' . self::AUTO_SCROLL_OPTION_NAME . '" name="' . self::AUTO_SCROLL_OPTION_NAME . '" value="1"' . (self::get_auto_scroll()?' checked="checked"':'') . ' />';
		$output .= '<p class="description">';
		$output .= __('If enabled, force-frame will scroll the browser\'s window to the top of the iframe each time the user navigates to a different url in the iframe (only if the top is not already visible).', FORCE_FRAME_TEXT_DOMAIN);
		$output .= '</p>';
		echo $output;
	}
	
	public static function create_auto_adjust_height_settings_field() {
		$output = '';
		$output .= '<input type="checkbox" id="' . self::AUTO_ADJUST_HEIGHT_OPTION_NAME . '" name="' . self::AUTO_ADJUST_HEIGHT_OPTION_NAME . '" value="1"' . (self::get_auto_adjust_height()?' checked="checked"':'') . ' />';
		$output .= '<p class="description">';
		$output .= __('If enabled, force-frame will compute and adjust the iframe height based on its contents whene it is first loaded and when the user navigates to a different url inside it. If you have specified the height attribute below, that value will be overridden by the computed value.', FORCE_FRAME_TEXT_DOMAIN);
		$output .= '</p>';
		echo $output;
	}
	
	public static function create_iframe_attributes_settings_field() {
		$output = '';
		$output .= '<textarea id="' . self::IFRAME_ATTRIBUTES_OPTION_NAME . '" name="' . self::IFRAME_ATTRIBUTES_OPTION_NAME . '" style="width: 400px; height: 200px;">';
		$output .= esc_textarea(get_option(self::IFRAME_ATTRIBUTES_OPTION_NAME, self::DEFAULT_IFRAME_ATTRIBUTES));
		$output .= '</textarea>';
		$output .= '<p class="description">';
		$output .= __('Insert here a list of attributes that will be added to the iframe. Add the attributes one per line, using the format <strong>attribute_name</strong>=<strong>attribute_value</strong>. For example:', FORCE_FRAME_TEXT_DOMAIN);
		$output .= '</p>';
		$output .= '<p><pre>';
		$output .= esc_html("width=500\nscrolling=no\nstyle=margin-left:100px; margin-right:50px;");
		$output .= '</pre></p>';
		echo $output;
	}
	
	public static function create_whitelisted_ips_settings_field() {
		$output = '';
		$output .= '<textarea id="' . self::WHITELISTED_IPS_OPTION_NAME . '" name="' . self::WHITELISTED_IPS_OPTION_NAME . '" style="width: 400px; height: 200px;">';
		$output .= esc_textarea(get_option(self::WHITELISTED_IPS_OPTION_NAME, ''));
		$output .= '</textarea>';
		$output .= '<p class="description">';
		$output .= __('Insert here a list of client IPs for which the site will not be forced inside the iframe, one per line, for example:', FORCE_FRAME_TEXT_DOMAIN);
		$output .= '</p>';
		$output .= '<p><pre>';
		$output .= esc_html("123.123.123.123\n234.234.234.234");
		$output .= '</pre></p>';
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
	
	public static function get_auto_scroll() {
		$value = get_option(self::AUTO_SCROLL_OPTION_NAME, self::DEFAULT_AUTO_SCROLL);
		return !empty($value);
	}
	
	public static function get_auto_adjust_height() {
		$value = get_option(self::AUTO_ADJUST_HEIGHT_OPTION_NAME, self::DEFAULT_AUTO_ADJUST_HEIGHT);
		return !empty($value);
	}
	
	public static function get_mode() {
		return get_option(self::MODE_OPTION_NAME, self::DEFAULT_MODE);
	}
	
	public static function get_iframe_attributes() {
		$attributesText = get_option(self::IFRAME_ATTRIBUTES_OPTION_NAME, self::DEFAULT_IFRAME_ATTRIBUTES);
		$attributesText = str_replace("\r", '', $attributesText);
		$attributeLines = explode("\n", $attributesText);
		$attributes = array();
		foreach($attributeLines as $line) {
			$name = $line;
			$value = '';
			$equalPos = strpos($line, '=');
			if($equalPos !== false) {
				$name = trim(substr($line, 0, $equalPos));
				$value = trim(substr($line, $equalPos + 1));
			}
			
			if($name == 'style') {
				$styleLines = explode(';', $value);
				$value = array();
				foreach($styleLines as $styleLine) {
					$colonPos = strpos($styleLine, ':');
					if($colonPos !== false) {
						$styleName = trim(substr($styleLine, 0, $colonPos));
						$styleValue = trim(substr($styleLine, $colonPos + 1));
						$value[$styleName] = $styleValue;
					}
				}
			}
			$attributes[$name] = $value;
		}
		
		return $attributes;
	}
	
	public static function get_whitelisted_ips() {
		$rawValue = get_option(self::WHITELISTED_IPS_OPTION_NAME, '');
		return explode("\n", $rawValue);
	}
}

class ForceFrame {
	const MODE_GET = 'get';
	const MODE_FRAGMENT = 'fragment';
	const PARENT_JS_AJAX_ACTION = 'force_frame_parent_js';
	
	public static function init() {
		// add with high priority so config data comes before js
		add_action('wp_head', array(__CLASS__, 'wp_head'), 0);
		add_action('wp_enqueue_scripts', array(__CLASS__, 'wp_enqueue_scripts'));
		add_action('wp_ajax_' . self::PARENT_JS_AJAX_ACTION, array(__CLASS__, 'parent_js'));
		add_action('wp_ajax_nopriv_' . self::PARENT_JS_AJAX_ACTION, array(__CLASS__, 'parent_js'));
		
		$scriptName = self::getJsFilename('force-frame');
		wp_register_script('force-frame.js', plugins_url('js/' . $scriptName, __FILE__), array('jquery'), FORCE_FRAME_VERSION);
	}
	
	public static function parent_js() {
		header('Content-Type: text/javascript');
		
		// ensure the plugin is enabled
		if(self::isEnabled()) {
			$parentJsConfig = array(
				'pluginUrl' => plugin_dir_url(__FILE__),
				'parentJsUrl' => self::getParentJsUrl(),
				'childUrl' => get_bloginfo('wpurl'),
				'getParam' => ForceFrameAdmin::get_get_param(),
				'useAbsoluteUrl' => ForceFrameAdmin::get_use_absolute_url(),
				'mode' => ForceFrameAdmin::get_mode(),
				'modeFragment' => ForceFrame::MODE_FRAGMENT,
				'modeGet' => ForceFrame::MODE_GET,
				'autoScroll' => ForceFrameAdmin::get_auto_scroll(),
				'autoAdjustHeight' => ForceFrameAdmin::get_auto_adjust_height(),
				'iframeAttributes' => ForceFrameAdmin::get_iframe_attributes()
			);
			
			echo 'var ForceFrameParentConfig = ' . json_encode($parentJsConfig) . ";\n";
			$jsPath = substr(plugin_dir_path(__FILE__), 0, -1) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR;
			// append easyxdm code
			$easyXDMPath = $jsPath . 'easyxdm' . DIRECTORY_SEPARATOR . self::getJsFilename('easyxdm');
			readfile($easyXDMPath);
			 
			$parentJsPath = $jsPath . self::getJsFilename('force-frame.parent');
			readfile($parentJsPath);
		}
		die();
	}
	
	public static function wp_enqueue_scripts() {
		if(self::isEnabled()) wp_enqueue_script('force-frame.js');
	}
	
	public static function wp_head() {
		// check the plugin is enabled
		if(self::isEnabled()) {
			$childJsConfig = array(
				'parentUrl' => ForceFrameAdmin::get_parent_url(),
				'childUrl' => get_bloginfo('wpurl'),
				'getParam' => ForceFrameAdmin::get_get_param(),
				'useAbsoluteUrl' => ForceFrameAdmin::get_use_absolute_url(),
				'mode' => ForceFrameAdmin::get_mode(),
				'modeFragment' => ForceFrame::MODE_FRAGMENT,
				'modeGet' => ForceFrame::MODE_GET
			);
?>
<script type="text/javascript">
var ForceFrameChildConfig = <?php echo json_encode($childJsConfig); ?>; 
</script>
<?php
		}
	}
	
	public static function isEnabled() {
		$parentUrl = ForceFrameAdmin::get_parent_url();
		$whitelistedIPs = ForceFrameAdmin::get_whitelisted_ips();
		$isClientWhiteListed = !empty($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $whitelistedIPs);
		return !empty($parentUrl) && !$isClientWhiteListed;
	}
	
	public static function getParentJsUrl() {
		return admin_url('admin-ajax.php') . '?action=' . self::PARENT_JS_AJAX_ACTION;
	}
	
	private static function getJsFilename($name) {
		if(!defined('WP_DEBUG') || !WP_DEBUG) $name .= '.min';
		$name .= '.js';
		return $name;
	}
}

// init plugin
function force_frame_init() {
	// load textdomain
	load_plugin_textdomain(FORCE_FRAME_TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages');
	
	ForceFrame::init();
	ForceFrameAdmin::init();	
}

add_action('init', 'force_frame_init');