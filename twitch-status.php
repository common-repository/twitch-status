<?php
/*
	Plugin Name: Twitch Status
	Description: Insert Twitch.tv online status in WordPress
	Version: 1.5.1
	Text Domain: twitch-status
	Author: Nicolas Bernier
	Author URI: http://www.synagila.com
	License: GPL v2

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

define('TWITCH_STATUS_BASE', plugin_dir_path(__FILE__));
define('TWITCH_STATUS_VER', '1.5.1');
define('TWITCH_STATUS_URL', plugins_url('/' . basename(dirname(__FILE__))));
define('TWITCH_STATUS_CACHE_TTL', 15);
define('TWITCH_STATUS_UPDATE_CRON_INTERVAL', 300);

include_once(TWITCH_STATUS_BASE . 'includes/twitch-status-options.php');
include_once(TWITCH_STATUS_BASE . 'includes/twitch-status-widget.php');

/**
 * Return the configured channels list
 * @return array
 */
function twitch_status_get_channels()
{
	$channels = array();

	// Get channels list
	if (get_option('twitch_status_channels'))
		$channels = get_option('twitch_status_channels');

	// Old single channel setting
	if (empty($channels) && get_option('twitch_status_channel')) {
		$channels[get_option('twitch_status_channel')] = array('name' => get_option('twitch_status_channel'), 'selectors' => explode("\n", get_option('twitch_status_selector')));

		// Convert old setting format to the new one
		update_option('twitch_status_channels', $channels, true);
		delete_option('twitch_status_channel');
		delete_option('twitch_status_selector');
	}

	// Init option if empty
	if (!get_option('twitch_status_channels'))
		update_option('twitch_status_channels', $channels, true);

	return $channels;
}

/**
 * Return data and translations for Javascript
 * @return type
 */
function twitch_status_get_js_vars()
{
	return array(
		'pluginUrl' => TWITCH_STATUS_URL,
		'ajaxurl' => admin_url('admin-ajax.php'),
		'channels' => twitch_status_get_channels(),
		'data' => twitch_status_get_channel_status(false),
		'buttonHTML' => array(
			'online' => __('LIVE!', 'twitch-status'),
			'offline' => __('offline', 'twitch-status'),
		),
		'msg' => array(
			'insertTwitchPlayer' => __('Insert Twitch player', 'twitch-status'),
			'insertTwitchChat' => __('Insert Twitch chat', 'twitch-status'),
			'insertTwitchStatus' => __('Insert Twitch online status tag', 'twitch-status'),
		),
	);
}

/**
 * Enqueue scripts and CSS
 * Called by enqueue_scripts action
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
	wp_register_script('twitch_status', TWITCH_STATUS_URL . '/js/twitch-status.js', array(), TWITCH_STATUS_VER, true);
	wp_localize_script('twitch_status', 'twitchStatus', twitch_status_get_js_vars());
	wp_enqueue_script('twitch_status');

	wp_enqueue_style('twitch_status', TWITCH_STATUS_URL . '/css/twitch-status.css', array(), TWITCH_STATUS_VER);
	wp_enqueue_style('twitch_status_fontello', TWITCH_STATUS_URL . '/font/fontello/css/fontello.css', array(), TWITCH_STATUS_VER);
	wp_enqueue_style('twitch_status_animation', TWITCH_STATUS_URL . '/font/fontello/css/animation.css', array(), TWITCH_STATUS_VER);
});

/**
 * Enqueue scripts and CSS for admin
 */
add_action('admin_enqueue_scripts', function () {
	wp_register_script('twitch_status_admin', TWITCH_STATUS_URL . '/js/twitch-status-admin.js', array(), TWITCH_STATUS_VER, true);
	wp_localize_script('twitch_status_admin', 'twitchStatus', twitch_status_get_js_vars());
	wp_enqueue_script('twitch_status_admin');
});

/**
 * Fetch token for Twitch Helix API calls
 * @return string
 */
function twitch_status_get_token()
{
	$token = get_option('twitch_status_token');
	$tokenType = get_option('twitch_status_token_type');
	$tokenExpiration = get_option('twitch_status_token_expiration');
	$now = time();

	// Refresh token
	if (empty($token) || empty($tokenType) || empty($tokenExpiration) || (($tokenExpiration - 30) <= $now)) {
		$ch = curl_init();
		curl_setopt_array($ch, $params = array(
			CURLOPT_POST => 1,
			CURLOPT_URL => 'https://id.twitch.tv/oauth2/token',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => http_build_query(array(
				'client_id' => get_option('twitch_status_client_id'),
				'client_secret' => get_option('twitch_status_client_secret'),
				'grant_type' => 'client_credentials',
				//'scope' => '',
			))
		));

		$result = @curl_exec($ch);

		if (!curl_error($ch)) {
			$data = @json_decode($result, true);
			$token = $data['access_token'];
			$tokenType = $data['token_type'];
			$tokenExpiration = $now + $data['expires_in'];
			update_option('twitch_status_token', $token, true);
			update_option('twitch_status_token_type', $tokenType, true);
			update_option('twitch_status_token_expiration', $tokenExpiration, true);
		} else {
			error_log('Failed to fetch new token from the Twitch Helix API: ' . curl_error($ch));
		}

		curl_close($ch);
	}

	return $token;
}

/**
 * Build HTTP query string from parameters for the Twitch Helix API
 * @param array $data
 * @return string
 */
function twitch_status_build_query($data)
{
	$paramStr = '';
	foreach ($data as $param => $value) {
		if (!is_array($value)) {
			$paramStr .= ($paramStr === '') ? '?' : '&';
			$paramStr .= urlencode($param) . '=' . urlencode($value);
		} else {
			foreach ($value as $v) {
				$paramStr .= ($paramStr === '') ? '?' : '&';
				$paramStr .= urlencode($param) . '=' . urlencode($v);
			}
		}
	}
	return $paramStr;
}

/**
 * Perform a call to the Twitch Helix API
 * @param string $endpoint API endpoint name
 * @param array $params API endpoint parameters
 * @return array
 */
function twitch_status_helix_call($endpoint, $params)
{
	$token = twitch_status_get_token();

	if (empty($token)) {
		return null;
	}

	$ch = curl_init();
	curl_setopt_array($ch, $curlParams = array(
		CURLOPT_URL => 'https://api.twitch.tv/helix/' . $endpoint . twitch_status_build_query($params),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array(
			'Client-ID: ' . get_option('twitch_status_client_id'),
			'Authorization: ' . ucfirst(get_option('twitch_status_token_type')) . ' ' . get_option('twitch_status_token'),
		)
	));

	$result = @curl_exec($ch);

	$data = null;
	$error = null;
	if (!curl_error($ch)) {
		$data = @json_decode($result, true);
		if ($data['error']) {
			$error = print_r($data, true);
			$data = null;
		}
	} else
		$error = curl_error($ch);

	if ($error) {
		error_log('Twitch Helix API call failed on ' . $endpoint . ' with params ' . twitch_status_build_query($params) . ' : ' . $error);
		error_log(print_r($curlParams, true));
	}

	curl_close($ch);

	return $data;
}

/**
 * Retrieve channel and stream data from Twitch.tv into an array
 * @param boolean $update Fetch actual status from twitch.tv API if true. Use cache only when false
 * @return array
 */
function twitch_status_get_channel_status($update = false)
{
	// Return cached data, if available
	$now = time();
	$cacheFile = TWITCH_STATUS_BASE . 'cache/channel-status.json';

	// Information is up to date
	if (!$update && (($now - @filemtime($cacheFile)) < TWITCH_STATUS_CACHE_TTL))
		return @json_decode(@file_get_contents($cacheFile), true);

	// Fetch new information from Twitch
	$jsonData = array();

	// Init returned data as "error"
	$channelNames = array();
	foreach (twitch_status_get_channels() as $channelName => $channel) {
		$channelNames[] = $channelName;
		$jsonData[$channelName] = array(
			'status' => 'error',
			'name' => $channelName,
		);
	}

	// Fetch channel information
	$twitchChannelData = twitch_status_helix_call('users', array(
		'login' => $channelNames
	));
	$channelIds = array();
	if (!empty($twitchChannelData) && !empty($twitchChannelData['data'])) {
		foreach ($twitchChannelData['data'] as $channelData) {
			$channelName = $channelData['login'];
			$channelIds[$channelData['id']] = $channelName;
			$jsonData[$channelName] = array_merge($jsonData[$channelName], $channelData);
			$jsonData[$channelName]['status'] = 'offline'; // Consider the channel is offline until we get the active streams
		}
	}

	// Fetch active stream information
	$twitchStreamData = twitch_status_helix_call('streams', array(
		'user_login' => $channelNames
	));
	$gameIds = array();
	if (!empty($twitchStreamData) && !empty($twitchStreamData['data'])) {
		foreach ($twitchStreamData['data'] as $streamData) {
			$gameIds[] = $streamData['game_id'];
			$channelName = $channelIds[$streamData['user_id']];
			$jsonData[$channelName] = array_merge($jsonData[$channelName], $streamData);
			$jsonData[$channelName]['status'] = 'online';
		}
	}

	// Fetch game information
	if (count($gameIds) > 0) {
		$twitchGameData = twitch_status_helix_call('games', array(
			'id' => $gameIds
		));
		$games = array();
		if (!empty($twitchGameData) && !empty($twitchGameData['data'])) {
			foreach ($twitchGameData['data'] as $gameData) {
				$games[$gameData['id']] = $gameData;
			}
		}
	}

	// Add HTML and text
	foreach ($jsonData as $channelName => $data) {
		if ($data['status'] !== 'error') {
			$game = $games[$data['game_id']];
			if (!empty($game)) {
				$data['game_name'] = $game['name'];
				$data['box_art_url'] = $game['box_art_url'];
			}

			$streamerLink = '<a href="https://twitch.tv/' . urlencode($data['login']) . '/profile" target="_blank">' . htmlspecialchars($data['display_name']) . '</a>';
			$gameLink = '<a href="https://twitch.tv/directory/game/' . str_replace('+', ' ', urlencode($data['game_id'])) . '" target="_blank">' . htmlspecialchars($data['game_name']) . '</a>';
			$parentDomain = parse_url(get_site_url(), PHP_URL_HOST);

			$data['statusTxt'] = ($data['status'] === 'online') ? __('Online', 'twitch-status') :  __('Offline', 'twitch-status');
			$data['playingHTML'] = sprintf(__("%s playing %s", 'twitch-status'), $streamerLink, $gameLink);
			$data['playerHTML'] = sprintf('<iframe src="https://player.twitch.tv/?channel=%s&parent=%s" frameborder="0" scrolling="no" allowfullscreen="true" class="twitch-player"></iframe>', $channelName, $parentDomain);
			$data['chatHTML'] = sprintf('<iframe src="https://www.twitch.tv/embed/%s/chat?parent=%s" frameborder="0" scrolling="no" allowfullscreen="true" class="twitch-chat"></iframe>', $channelName, $parentDomain);

			$jsonData[$channelName] = $data;
		}
	}

	file_put_contents($cacheFile, json_encode($jsonData));

	return $jsonData;
}

/**
 * Return HTML code for Twitch player
 * @param string $channel
 * @param boolean $withChat = false
 * @return string
 */
function twitch_status_get_player($channel, $withChat = false)
{
	return '
	<div class="twitch-player-container ' . ($withChat ? 'with-chat' : '') . '" data-twitch-channel="' . $channel . '">

		<div class="twitch-player-col">
			<div class="twitch-widget">
			<div class="twitch-preview twitch-is-online">
				<div class="twitch-thumbnail" data-twitch-data="playerHTML"></div>
				<!-- <span class="twitch-followers"></span> -->
				<span class="twitch-viewers"></span>
				<div class="twitch-channel-topic twitch-is-online"></div>
				<div class="twitch-game twitch-is-online"></div>
			</div>
			<div class="twitch-preview-offline">
				<a data-twitch-attr-channel-url="href" target="_blank">
				<div class="twitch-thumbnail-offline">
					<div class="twitch-offline-image"></div>
					<div class="twitch-offline-caption"></div>
				</div>
				</a>
			</div>
			</div>
		</div>
		' . ($withChat ? '<div class="twitch-chat-col" data-twitch-data="chatHTML"></div>' : '') . '
	</div>';
}

/**
 * Return HTML code for Twitch chat
 * @param string $channel
 * @return string
 */
function twitch_status_get_chat($channel)
{
	return '<div data-twitch-channel="' . $channel . '" data-twitch-data="chatHTML"></div>';
}

/**
 * Return HTML code for Twitch online tag
 * @param string $channel
 * @return string
 */
function twitch_status_get_tag($channel)
{
	return '<span class="twitch-status-tag twitch-status-channel-' . $channel . '"  data-twitch-channel="' . $channel . '"></span>';
}

/**
 * Embeded player shortcode
 */
add_shortcode('twitch-player', function ($atts) {
	$atts = shortcode_atts(array(
		'chat' => false,
		'channel' => '',
	), $atts);

	$withChat = ($atts['chat'] == 'true');

	return twitch_status_get_player($atts['channel'], $withChat);
});

/**
 * Embedded chat shortcode
 */
add_shortcode('twitch-chat', function ($atts) {
	$atts = shortcode_atts(array(
		'channel' => '',
	), $atts);

	return twitch_status_get_chat($atts['channel']);
});

/**
 * Status tag shortcode
 */
add_shortcode('twitch-status', function ($atts) {
	$atts = shortcode_atts(array(
		'channel' => '',
	), $atts);

	return twitch_status_get_tag($atts['channel']);
});

/**
 * Add shortcode buttons in editor toolbar
 */
add_filter("mce_external_plugins", function ($plugin_array) {
	$plugin_array["twitch_status_plugin"] = plugin_dir_url(__FILE__) . 'js/twitch-status-tinymce.js';
	return $plugin_array;
});

add_filter("mce_buttons", function ($buttons) {
	array_push($buttons, "twitch_player");
	array_push($buttons, "twitch_chat");
	array_push($buttons, "twitch_status");
	return $buttons;
});

/**
 * Retrieve channel and stream data from Twitch.tv
 * Called by AJAXaction
 * @return void
 */
function twitch_status_get_channel_status_ajax()
{
	header('Content-type: application/json; charset=utf-8');

	$jsonData = twitch_status_get_channel_status();

	echo json_encode($jsonData);

	die();
}

add_action('wp_ajax_get_twitch_channel_status', 'twitch_status_get_channel_status_ajax');
add_action('wp_ajax_nopriv_get_twitch_channel_status', 'twitch_status_get_channel_status_ajax');

/**
 * Initializes localization
 * Called by plugins_loaded action
 * @return void
 */
function twitch_status_lang_init()
{
	load_plugin_textdomain('twitch-status', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('plugins_loaded', 'twitch_status_lang_init');

/**
 * Scheduled status update
 * Called by wp_schedule_event
 * @return void
 */
function twitch_status_get_channel_status_cron()
{
	twitch_status_get_channel_status(true);
}

/**
 * Register CRON Twitch status update task
 */
add_action('twitch_status_cron_hook', 'twitch_status_get_channel_status_cron');

// Register CRON interval
add_filter('cron_schedules', function ($schedules) {
	$schedules['twitch-status-cron-interval'] = array(
		'interval' => TWITCH_STATUS_UPDATE_CRON_INTERVAL,
		'display' => esc_html__("Twitch Status interval", 'twitch-status')
	);

	return $schedules;
});

// Register CRON task
if (!wp_next_scheduled('twitch_status_cron_hook'))
	wp_schedule_event(time(), 'twitch-status-cron-interval', 'twitch_status_cron_hook');

// Unregister CRON task
register_deactivation_hook(__FILE__, function () {
	$timestamp = wp_next_scheduled('twitch_status_cron_hook');
	wp_unschedule_event($timestamp, 'twitch_status_cron_hook');
});

/**
 * Show a warning in the admin section if the plugin is not properly configured after update.
 */
function twitch_status_general_admin_notice()
{
	global $pagenow;
	if ($pagenow == 'index.php' && (empty(get_option('twitch_status_client_id')) || empty(get_option('twitch_status_client_secret')))) {
		$url = get_admin_url(null, 'options-general.php?page=twitch_status');

		echo '<div class="notice notice-error is-dismissible">';
		echo '<p><span aria-hidden="true" class="dashicons dashicons-warning" style="color: #df3232"></span> ' . __("You must set a Twitch application client ID and client secret for Twitch Status to make it work properly.", 'twitch-status') . '</p>';
		echo '<p><a class="button button-primary" href="' . $url . '">' . __('Configure Twitch Status', 'twitch-status') . '</a></p>';
		echo '</div>';
	}
}
add_action('admin_notices', 'twitch_status_general_admin_notice');
