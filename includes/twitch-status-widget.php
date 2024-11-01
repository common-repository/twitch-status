<?php

class TwitchStatus_Widget extends WP_Widget
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(
			'twitch-status_widget',
			__('Twitch status widget', 'twitch-status'),
			array('description' => __('Shows a preview of your Twitch.tv stream.', 'twitch-status'),)
		);
	}

	/**
	 * Widget front end
	 * @param array $args
	 * @param array $instance
	 */
	public function widget($args, $instance)
	{
		$title = apply_filters('widget_title', $instance['title']);

		// Use the first available channel for backward compatibility with v1.1
		if (empty($instance['channel']))
			$instance['channel'] = reset(array_keys(twitch_status_get_channels()));

		// Get click target URL
		switch (@$instance['target']) {
			case 'url':
				$targetUrl = @$instance['url'];
				break;

			case 'channel':
				$targetUrl = 'https://twitch.tv/' . $instance['channel'];
				break;

			case 'page':
				$targetUrl = get_permalink(@$instance['page']);
				break;

			default:
				$targetUrl = null;
		}

		if (!empty($targetUrl)) {
			$linkAttr = ' href="' . $targetUrl . '"';
			if (!empty($instance['newtab']))
				$linkAttr .= ' target="_blank"';
		} else
			$linkAttr = '';

		echo $args['before_widget'];

		if (!empty($title))
			echo $args['before_title'] . $title . $args['after_title'];
?>
		<div class="twitch-widget twitch-status-channel-<?php echo $instance['channel']; ?>" data-twitch-channel="<?php echo $instance['channel']; ?>">
			<div class="twitch-preview" style="display: none">
				<div class="twitch-channel-topic"></div>
				<div class="twitch-game"></div>
				<div class="twitch-thumbnail">
					<a<?php echo $linkAttr; ?>>
						<div class="twitch-thumbnail-image"></div>
						<div class="twitch-play-button"></div>
						</a>
				</div>
				<!-- <span class="twitch-followers"></span> -->
				<span class="twitch-viewers"></span>
			</div>
			<div class="twitch-preview-offline">
				<a<?php echo $linkAttr; ?>>
					<div class="twitch-thumbnail-offline">
						<div class="twitch-offline-image"></div>
						<div class="twitch-offline-caption"></div>
					</div>
					</a>
			</div>
		</div>
	<?php
		echo $args['after_widget'];
	}

	/**
	 * Widget admin form
	 * @param array $instance
	 */
	public function form($instance)
	{
		if (!isset($instance['title']))
			$instance['title'] = __('Twitch', 'twitch-status');

		if (!isset($instance['target']))
			$instance['target'] = 'channel';

		// Use the first available channel for backward compatibility with v1.1
		if (empty($instance['channel']))
			$instance['channel'] = reset(array_keys(twitch_status_get_channels()));
	?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" />

			<label for="<?php echo $this->get_field_id('channel'); ?>"><?php _e('Channel:', 'twitch-status'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('channel'); ?>" name="<?php echo $this->get_field_name('channel'); ?>">
				<?php
				foreach (twitch_status_get_channels() as $channel)
					echo '<option value="' . esc_attr($channel['name']) . '"' . (($channel['name'] == $instance['channel']) ? ' selected' : '') . '>' . esc_attr($channel['name']) . '</option>';
				?>
			</select>

			<label><?php echo __("\"Play\" button target", 'twitch-status') ?></label><br />
			<input type="radio" name="<?php echo $this->get_field_name('target'); ?>" id="<?php echo $this->get_field_id('target_url'); ?>" value="url" <?php echo ((@$instance['target'] == 'url') ? ' checked="checked"' : '') ?>>
			<label for="<?php echo $this->get_field_id('target_url'); ?>"><?php echo __('URL:', 'twitch-status') ?></label>
			<input type="text" name="<?php echo $this->get_field_name('url'); ?>" value="<?php echo esc_attr(@$instance['url']) ?>"><br />

			<input type="radio" name="<?php echo $this->get_field_name('target'); ?>" id="<?php echo $this->get_field_id('target_channel'); ?>" value="channel" <?php echo ((@$instance['target'] == 'channel') ? ' checked="checked"' : '') ?>>
			<label for="<?php echo $this->get_field_id('target_channel'); ?>"><?php echo __('Your Twitch.tv channel', 'twitch-status') ?></label><br />

			<input type="radio" name="<?php echo $this->get_field_name('target'); ?>" id="<?php echo $this->get_field_id('target_page'); ?>" value="page" <?php echo ((@$instance['target'] == 'page') ? ' checked="checked"' : '') ?>>
			<label for="<?php echo $this->get_field_id('target_page'); ?>"><?php echo __('Blog page:', 'twitch-status') ?></label>

			<select name="<?php echo $this->get_field_name('page'); ?>" id="<?php echo $this->get_field_id('page'); ?>">
				<?php
				foreach (get_pages() as $aPage)
					echo '<option value="' . $aPage->ID . '"' . ((@$instance['page'] == $aPage->ID) ? ' selected' : '') . '>' . htmlspecialchars($aPage->post_title) . '</option>';
				?>
			</select><br />

			<input type="checkbox" name="<?php echo $this->get_field_name('newtab'); ?>" id="<?php echo $this->get_field_id('newtab'); ?>" value="newtab" <?php echo ((@$instance['newtab']) ? ' checked="checked"' : '') ?>>
			<label for="<?php echo $this->get_field_id('newtab'); ?>"><?php echo __('Open in a new tab', 'twitch-status') ?></label>
		</p>
<?php
	}

	/**
	 * Updating widget replacing old instances with new
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update($new_instance, $old_instance)
	{
		$instance			 = array();
		$instance['title']	 = trim((!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '');
		$instance['target']	 = (!empty($new_instance['target'])) ? $new_instance['target'] : 'channel';
		$instance['page']	 = (!empty($new_instance['page'])) ? $new_instance['page'] : '';
		$instance['newtab']	 = !empty($new_instance['newtab']);
		$instance['url']	 = trim((!empty($new_instance['url'])) ? $new_instance['url'] : '');
		$instance['channel'] = $new_instance['channel'];

		return $instance;
	}

	/**
	 * Register the widget
	 * called by widgets_init action
	 * @return void
	 */
	public static function register()
	{
		register_widget('TwitchStatus_Widget');
	}
}

// Register and load the widget
add_action('widgets_init', array('TwitchStatus_Widget', 'register'));
