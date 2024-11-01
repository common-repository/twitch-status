<?php

/**
 * Add the admin options page
 * Called by admin_menu action
 * @return void
 */
function twitch_status_admin_add_page()
{
	add_options_page('Twitch status', 'Twitch status', 'manage_options', 'twitch_status', 'twitch_status_options_page');
}

add_action('admin_menu', 'twitch_status_admin_add_page');

/**
 * Admin options page
 */
function twitch_status_options_page()
{
?>
	<div>
		<h2>Twitch Status</h2>
		<form action="options.php" method="post">
			<?php settings_fields('twitch_status_options'); ?>
			<?php do_settings_sections('twitch_status'); ?>
			<input name="Submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form>
	</div>

<?php
}

/**
 * Init admin options
 */
function twitch_status_admin_init()
{
	twitch_status_get_channels();
	add_settings_section('twitch_status_section_application', __('Application settings', 'twitch-status'), 'twitch_status_application_settings', 'twitch_status');

	register_setting('twitch_status_options', 'twitch_status_client_id', 'twitch_status_client_id_validate');
	add_settings_field('twitch_status_client_id', __('Client ID', 'twitch-status'), 'twitch_status_client_id_edit', 'twitch_status', 'twitch_status_section_application');

	register_setting('twitch_status_options', 'twitch_status_client_secret', 'twitch_status_client_secret_validate');
	add_settings_field('twitch_status_client_secret', __('Client secret', 'twitch-status'), 'twitch_status_client_secret_edit', 'twitch_status', 'twitch_status_section_application');

	add_settings_section('twitch_status_section_channels', __('Channel settings', 'twitch-status'), null, 'twitch_status');
	register_setting('twitch_status_options', 'twitch_status_channels', 'twitch_status_channels_validate');
	add_settings_field('twitch_status_channels', __('Channels', 'twitch-status'), 'twitch_status_channels_edit', 'twitch_status', 'twitch_status_section_channels');
}

add_action('admin_init', 'twitch_status_admin_init');

function twitch_status_application_settings()
{
	$url = 'https://dev.twitch.tv/console/apps/create';
	$link = '<a href="' . $url . '" target="_blank">' . $url . '</a>';

	echo '<ol>';
	echo '<li>' . sprintf(__("Head to %s and create a new application for your website.", 'twitch-status'), $link) . '</li>';
	echo '<li>' . __("Get client ID and client secret then paste them in the fields below.", 'twitch-status') . '</li>';
	echo '</ol>';
}

function twitch_status_client_id_edit()
{
	echo '<input type="text" name="twitch_status_client_id" size="32" value="' . esc_attr(get_option('twitch_status_client_id')) . '">';
}

function twitch_status_client_secret_edit()
{
	echo '<input type="password" name="twitch_status_client_secret" size="32" value="' . esc_attr(get_option('twitch_status_client_secret')) . '">';
	echo '<br><i>' . __("Warning: Client secret is equivalent to a password and must be kept confidential.", 'twitch-status') . '</i>';
}

function twitch_status_client_id_validate($input)
{
	return trim($input);
}

function twitch_status_client_secret_validate($input)
{
	return trim($input);
}

function twitch_status_channels_edit()
{
?>
	<script type="text/javascript">
		function twitchStatus_AddChannelRow() {
			jQuery('#twitchStatusChannels tbody').append(jQuery('#twitchStatusChannels tbody tr:last()').clone());
			jQuery('#twitchStatusChannels tbody tr:last() button').show();
		}

		function twitchStatus_RemoveChannelRow(button) {
			jQuery(button).closest('tr').remove();
		}
	</script>

<?php
	$channels = twitch_status_get_channels();

	if (empty($channels))
		$channels[] = array('name' => '', 'selectors' => array());

	echo '<table id="twitchStatusChannels">';
	echo '<thead>';
	echo '<tr>';
	echo '<th style="width: 30px"></th>';
	echo '<th>' . __('Channel name', 'twitch-status') . '<p class="description" style="font-weight: normal">' . __("Your Twitch channel(s) name(s).<br />Set it even if you just want to add a widget.", 'twitch-status') . '</p></th>';
	echo '<th style="width: 500px">' . __('jQuery selectors (optional)', 'twitch-status') . '<p class="description" style="font-weight: normal">' . __("<a href=\"http://api.jquery.com/category/selectors/\" target=\"_blank\">jQuery selectors</a> matching the places where you want to insert the stream status tags.<br />Enter one selector per line. You can add as much selectors as you like.<br />Leave this blank if you just want to use a widget for the channel.", 'twitch-status') . '</p></th>';
	echo '</tr>';
	echo '</thead>';

	echo '<tbody>';

	$first = true;
	foreach ($channels as $channel) {
		echo '<tr>';
		echo '<td style="width: 30px"><button class="button button-primary" type="button" style="' . ($first ? 'display: none' : '') . '" onclick="twitchStatus_RemoveChannelRow(this)">X</button></td>';
		echo '<td><input name="twitch_status_channels[names][]" size="40" type="text" value="' . esc_attr($channel['name']) . '" /></td>';
		echo '<td><textarea class="large-text code" name="twitch_status_channels[selectors][]" cols="40" rows="3">' . htmlspecialchars(implode("\n", $channel['selectors'])) . '</textarea></td>';
		echo '</tr>';

		$first = false;
	}

	echo '</tbody>';

	echo '<tfoot>';
	echo '<tr><td colspan="3"><button class="button button-primary" type="button" onclick="twitchStatus_AddChannelRow()">' . __('Add channel', 'twitch-status') . '</button></td></tr>';
	echo '</tfoot>';
	echo '</table>';
}

function twitch_status_channels_validate($input)
{
	$channels = array();

	if (empty($input['names']))
		return $channels;

	foreach (array_keys($input['names']) as $i) {
		$name = preg_replace('/[^0-9a-z_-]+/', '', strtolower(trim($input['names'][$i])));

		if (empty($name))
			continue;

		$selectors = $input['selectors'][$i];

		$selectors	 = trim(str_replace("\r", "", $selectors));
		$rows		 = explode("\n", $selectors);

		$filtered	 = array();
		foreach ($rows as $row)
			if (trim($row) != "")
				$filtered[]	 = trim($row);

		$channels[$name] = array('name' => $name, 'selectors' => $filtered);
	}

	return $channels;
}
