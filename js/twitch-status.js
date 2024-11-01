jQuery(document).ready(function () {

	// Add Twitch online button containers
	for (var channel in twitchStatus.channels)
		for (var i in twitchStatus.channels[channel].selectors)
			jQuery(twitchStatus.channels[channel].selectors[i]).append('<span class="twitch-status-tag twitch-status-channel-' + channel + '" data-twitch-channel="' + channel + '"></span>');

	twitchStatusUpdate(); // Update Twitch status elements
	setInterval(twitchStatusUpdate, 10000); // Update every 10 seconds

	// Refresh widget after resize
	jQuery(window).resize(twitchStatusRefreshWidgetSize);
	window.addEventListener("orientationchange", twitchStatusRefreshWidgetSize, false);

	twitchStatusRefresh(); // Update HTML elements with existing cached data
});

/**
 * Update Twitch Status HTML elements from AJAX
 * @returns {undefined}
 */
function twitchStatusUpdate() {
	var data = {
		'action': 'get_twitch_channel_status'
	};

	jQuery.post(twitchStatus.ajaxurl, data, function (response, status) {
		if (status !== 'success')
			return;

		twitchStatus.data = response;

		twitchStatusRefresh();
	});
}

/**
 * Update HTML5 elements with provided data
 * @param {string} prefix
 * @param {object} data
 * @param {string} channelName
 * @returns {undefined}
 */
function twitchStatusUpdateHtmlData(prefix, data, channelName) {

	var channelElements = jQuery('[data-twitch-channel=' + channelName + ']');

	for (var key in data) {

		// Proceed with sub elements
		if ((typeof data[key] == 'object') && (data[key] !== null))
			twitchStatusUpdateHtmlData(key + '-', data[key], channelName);

		else {

			// Update HTML attribute values
			var attrSelector = '[data-twitch-attr-' + prefix + key + ']'
			channelElements.find(attrSelector).addBack(attrSelector).each(function () {
				var attr = jQuery(this).attr('data-twitch-attr-' + prefix + key).split(',');

				for (var i in attr)
					jQuery(this).attr(attr[i], data[key]);
			});

			// Update inner HTML
			var dataSelector = '[data-twitch-data=' + prefix + key + ']';
			channelElements.find(dataSelector).addBack(dataSelector).each(function () {

				var element = jQuery(this);

				// Do not update if it's an embedded player and it has not been modified to avoid reload
				if (key.match(/^(player)/) && ((data[key] === '') === (element.html() === '')))
					return;

				// Do not update chat once it has been initialized
				if (key.match(/^(chat)/) && (element.html() !== ''))
					return;

				if (key.match(/HTML$/))
					element.html(data[key]);
				else
					element.text(data[key]);
			});
		}
	}
}

/**
 * Update Twitch Status HTML elements with existing data
 * @returns {undefined}
 */
function twitchStatusRefresh() {
	for (var c in twitchStatus.data) {
		var channel = twitchStatus.data[c];

		if (channel.status === 'error')
			continue;

		var tag = jQuery('.twitch-status-tag[data-twitch-channel=' + channel.name + '], .twitch-status-tag.twitch-status-channel-' + channel.name);

		// Update status button
		if (twitchStatus.data[channel.name].status === 'online') {
			tag.removeClass('twitch-offline');
			tag.addClass('twitch-online');
			tag.html(twitchStatus.buttonHTML.online);
		}
		else {
			tag.removeClass('twitch-online');
			tag.addClass('twitch-offline');
			tag.html(twitchStatus.buttonHTML.offline);
		}

		// Update custom elements
		twitchStatusUpdateHtmlData('', twitchStatus.data[c], channel.name);

		// Refresh widget
		twitchStatusRefreshWidget(channel.name);
	}

	twitchStatusRefreshWidgetSize();
}

/**
 * Refresh Twitch Status widget and player size
 * @returns {undefined}
 */
function twitchStatusRefreshWidgetSize() {

	// Set narrow mode to Twitch player with chat if the container is not wide enough
	jQuery('.twitch-player-container.with-chat').each(function () {
		var w = jQuery(this).parent().width();

		if (w <= 770)
			jQuery(this).addClass('narrow-player');
		else
			jQuery(this).removeClass('narrow-player');
	});


	// Resize widgets
	jQuery('.twitch-widget').each(function () {
		var w = jQuery(this).width();
		var h = w / (16 / 9);

		jQuery(this).find('.twitch-offline-image').css({ width: w + 'px', height: h + 'px' });
		jQuery(this).find('.twitch-play-button, .twitch-offline-caption').css({ lineHeight: h + 'px', width: w + 'px', height: h + 'px', marginTop: -h + 'px' });
	});

	// Resize players
	jQuery('.twitch-player').each(function () {
		var w = jQuery(this).parent().width();
		var h = w / (16 / 9);

		jQuery(this).css({ width: w + 'px', height: h + 'px' });
	});

	// Resize chat windows
	jQuery('.twitch-player-container .twitch-chat-col').each(function () {
		var h = jQuery(this).parent().find('.twitch-player-col').height();
		var windowH = jQuery(window).height();

		if (jQuery(this).closest('.twitch-player-container').is('.narrow-player')) {
			var h = Math.max(200, windowH - h - 30);
		}

		jQuery(this).find('iframe').css({ height: h + 'px', 'min-height': '0px' });
	});
}

/**
 * Update Twitch status widget
 * @param {string} channelName
 * @returns {undefined}
 */
function twitchStatusRefreshWidget(channelName) {

	var channelElements = jQuery('[data-twitch-channel=' + channelName + '], .twitch-status-channel-' + channelName);
	var loadingElements = channelElements.find('.twitch-is-loading').addBack('.twitch-is-loading');
	var onlineElements = channelElements.find('.twitch-is-online').addBack('.twitch-is-online');
	var offlineElements = channelElements.find('.twitch-is-offline').addBack('.twitch-is-offline');

	var channel = twitchStatus.data[channelName];

	loadingElements.hide();

	if (!channel) {
		return;
	}

	if (channel.status === 'online') {
		channelElements.find('.twitch-channel-topic').html(channel.title);
		channelElements.find('.twitch-game').html(channel.game_name);

		channelElements.find('.twitch-viewers').html(channel.viewer_count);
		//channelElements.find('.twitch-followers').html(channel.followers); // TODO: Not available

		channelElements.find('.twitch-thumbnail-image').each(function(index, element) {
			var width = jQuery(element.parentElement.parentElement.parentElement).width();
			var height = Math.round(width * 9 / 16);
			var url = channel.thumbnail_url.replace('{width}', width).replace('{height}', height);
			channelElements.find('.twitch-thumbnail-image').html('<img src="' + url + '">');
		});

		channelElements.find('.twitch-preview').show();
		channelElements.find('.twitch-preview-offline').hide();

		offlineElements.hide();
		onlineElements.show();
	}
	else {
		if (channel.status !== 'error') {
			channelElements.find('.twitch-offline-image').html('<img src="' + channel.offline_image_url + '">');
		} else {
			channelElements.find('.twitch-offline-image').empty();
		}

		channelElements.find('.twitch-preview').hide();
		channelElements.find('.twitch-offline-caption').text(channel.statusTxt);
		channelElements.find('.twitch-preview-offline').show();

		onlineElements.hide();
		offlineElements.show();
	}
}