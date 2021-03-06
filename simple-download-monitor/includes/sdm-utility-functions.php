<?php

/**
 * Get (filtered) list of all download button colors.
 * @return array Array of colors: color key => color name.
 */
function sdm_get_download_button_colors() {
    return apply_filters('sdm_download_button_color_options', array(
	'green' => __('Green', 'simple-download-monitor'),
	'blue' => __('Blue', 'simple-download-monitor'),
	'purple' => __('Purple', 'simple-download-monitor'),
	'teal' => __('Teal', 'simple-download-monitor'),
	'darkblue' => __('Dark Blue', 'simple-download-monitor'),
	'black' => __('Black', 'simple-download-monitor'),
	'grey' => __('Grey', 'simple-download-monitor'),
	'pink' => __('Pink', 'simple-download-monitor'),
	'orange' => __('Orange', 'simple-download-monitor'),
	'white' => __('White', 'simple-download-monitor')
	    ));
}

function sdm_get_download_count_for_post($id) {
    // Get number of downloads by counting db columns matching postID
    global $wpdb;
    $table = $wpdb->prefix . 'sdm_downloads';
    $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $table . ' WHERE post_id=%s', $id));
    // Count database rows
    $db_count = $wpdb->num_rows;

    // Check post meta to see if we need to offset the count before displaying to viewers
    $get_offset = get_post_meta($id, 'sdm_count_offset', true);

    if ($get_offset && $get_offset != '') {

	$db_count = $db_count + $get_offset;
    }

    return $db_count;
}

function sdm_get_item_description_output($id) {
    $item_description = get_post_meta($id, 'sdm_description', true);
    $isset_item_description = isset($item_description) && !empty($item_description) ? $item_description : '';
//$isset_item_description = apply_filters('the_content', $isset_item_description);

    $isset_item_description = do_shortcode($isset_item_description);
    $isset_item_description = wptexturize($isset_item_description);
    $isset_item_description = convert_smilies($isset_item_description);
    $isset_item_description = convert_chars($isset_item_description);
    $isset_item_description = wpautop($isset_item_description);
    $isset_item_description = shortcode_unautop($isset_item_description);
    $isset_item_description = prepend_attachment($isset_item_description);
    return $isset_item_description;
}

function sdm_get_password_entry_form($id, $args = array(), $class = '') {
    $action_url = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL . '/?smd_process_download=1&download_id=' . $id;

//Get the download button text
    $button_text = isset($args['button_text']) ? $args['button_text'] : '';
    if (empty($button_text)) {//Use the default text for the button
	$button_text_string = __('Download Now!', 'simple-download-monitor');
    } else {//Use the custom text
	$button_text_string = $button_text;
    }

    $uuid = uniqid('sdm-pass-');

    $data = __('Enter Password to Download:', 'simple-download-monitor');
    $data .= '<form action="' . $action_url . '" method="post" id="' . $uuid . '">';
    $data .= '<input type="password" name="pass_text" class="sdm_pass_text" value="" /> ';
    $data .= '<span class="sdm-download-button">';
    $data .= '<a href="javascript:document.getElementById(\'' . $uuid . '\').submit();" name="sdm_dl_pass_submit" class="pass_sumbit sdm_pass_protected_download ' . $class . '">' . $button_text_string . '</a>';
    $data .= '</span>';
    $data .= '<input type="hidden" name="download_id" value="' . $id . '" />';
    $data .= '</form>';
    return $data;
}

/**
 * Get remote IP address.
 * @link http://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
 *
 * @param bool $ignore_private_and_reserved Ignore IPs that fall into private or reserved IP ranges.
 * @return mixed IP address as a string or null, if remote IP address cannot be determined (or is ignored).
 */
function sdm_get_ip_address($ignore_private_and_reserved = false) {
    $flags = $ignore_private_and_reserved ? (FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) : 0;
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
	if (array_key_exists($key, $_SERVER) === true) {
	    foreach (explode(',', $_SERVER[$key]) as $ip) {
		$ip = trim($ip); // just to be safe

		if (filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false) {
		    return $ip;
		}
	    }
	}
    }
    return null;
}

/**
 * Get location information (country or other info) for given IP address.
 * @param string $ip
 * @param string $purpose
 * @return mixed
 */
function sdm_ip_info($ip, $purpose = "location") {

    $continents = array(
	"AF" => "Africa",
	"AN" => "Antarctica",
	"AS" => "Asia",
	"EU" => "Europe",
	"OC" => "Australia (Oceania)",
	"NA" => "North America",
	"SA" => "South America"
    );

    $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));

    if (@strlen(trim($ipdat->geoplugin_countryCode)) === 2) {
	switch ($purpose) {
	    case "location":
		return array(
		    "city" => @$ipdat->geoplugin_city,
		    "state" => @$ipdat->geoplugin_regionName,
		    "country" => @$ipdat->geoplugin_countryName,
		    "country_code" => @$ipdat->geoplugin_countryCode,
		    "continent" => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
		    "continent_code" => @$ipdat->geoplugin_continentCode
		);
	    case "address":
		$address = array($ipdat->geoplugin_countryName);
		if (@strlen($ipdat->geoplugin_regionName) >= 1)
		    $address[] = $ipdat->geoplugin_regionName;
		if (@strlen($ipdat->geoplugin_city) >= 1)
		    $address[] = $ipdat->geoplugin_city;
		return implode(", ", array_reverse($address));
	    case "city":
		return @$ipdat->geoplugin_city;
	    case "state":
		return @$ipdat->geoplugin_regionName;
	    case "region":
		return @$ipdat->geoplugin_regionName;
	    case "country":
		return @$ipdat->geoplugin_countryName;
	    case "countrycode":
		return @$ipdat->geoplugin_countryCode;
	}
    }

    // Either no info found or invalid $purpose.
    return null;
}

/*
 * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
 */

function sdm_sanitize_value_by_array($to_check, $valid_values) {
    $keys = array_keys($valid_values);
    $keys = array_map('strtolower', $keys);
    if (in_array($to_check, $keys)) {
	return $to_check;
    }
    return reset($keys); //Return the first element from the valid values
}

function sdm_get_logged_in_user() {
    $visitor_name = false;

    if (is_user_logged_in()) {  // Get WP user name (if logged in)
	$current_user = wp_get_current_user();
	$visitor_name = $current_user->user_login;
    }

    //WP eMember plugin integration
    if (class_exists('Emember_Auth')) {
	//WP eMember plugin is installed.
	$emember_auth = Emember_Auth::getInstance();
	$username = $emember_auth->getUserInfo('user_name');
	if (!empty($username)) {//Member is logged in.
	    $visitor_name = $username; //Override the visitor name to emember username.
	}
    }

    return $visitor_name;
}

// Checks if current visitor is a bot
function sdm_visitor_is_bot() {
    $bots = array('archiver', 'baiduspider', 'bingbot', 'binlar', 'casper', 'checkprivacy', 'clshttp', 'cmsworldmap', 'comodo', 'curl', 'diavol', 'dotbot', 'DuckDuckBot', 'Exabot', 'email', 'extract', 'facebookexternalhit', 'feedfinder', 'flicky', 'googlebot','grab', 'harvest', 'httrack', 'ia_archiver', 'jakarta', 'kmccrew', 'libwww', 'loader', 'MJ12bot', 'miner', 'msnbot', 'nikto', 'nutch', 'planetwork', 'purebot', 'pycurl', 'python', 'scan', 'skygrid', 'slurp', 'sucker', 'turnit', 'vikspider', 'wget', 'winhttp', 'yandex', 'yandexbot', 'yahoo', 'youda', 'zmeu', 'zune');

    $isBot = false;

    $user_agent = wp_kses_data($_SERVER['HTTP_USER_AGENT']);

    foreach ($bots as $bot) {
	if (stripos($user_agent, $bot) !== false) {
	    $isBot = true;
	}
    }

    if (empty($user_agent) || $user_agent == ' ') {
	$isBot = true;
    }

    //This filter can be used to override what you consider bot via your own custom function. You can read the user-agent value from the server var.
    $isBot = apply_filters('sdm_visitor_is_bot', $isBot);
    
    return $isBot;
}
