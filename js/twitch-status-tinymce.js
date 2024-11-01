( function () {
	tinymce.create( "tinymce.plugins.twitch_status_plugin", {
		init: function ( ed, url ) {

			// Add Twitch player button
			ed.addButton( "twitch_player", {
				title: twitchStatus.msg.insertTwitchPlayer,
				cmd: "insert_twitch_player",
				image: twitchStatus.pluginUrl + "/assets/GlitchBadge_Purple_256px.png"
			} );

			ed.addCommand( "insert_twitch_player", function () {
				var selected_text = ed.selection.getContent();
				var return_text = '[twitch-player channel="' + selected_text + '" chat="true"]';
				ed.execCommand( "mceInsertContent", 0, return_text );
			} );

			// Add Twitch chat button
			ed.addButton( "twitch_chat", {
				title: twitchStatus.msg.insertTwitchChat,
				cmd: "insert_twitch_chat",
				image: twitchStatus.pluginUrl + "/assets/GlitchBadge_White_256px.png"
			} );

			ed.addCommand( "insert_twitch_chat", function () {
				var selected_text = ed.selection.getContent();
				var return_text = '[twitch-chat channel="' + selected_text + '"]';
				ed.execCommand( "mceInsertContent", 0, return_text );
			} );

			// Add Twitch online status
			ed.addButton( "twitch_status", {
				title: twitchStatus.msg.insertTwitchStatus,
				cmd: "insert_twitch_status",
				image: twitchStatus.pluginUrl + "/assets/live-button.png"
			} );

			ed.addCommand( "insert_twitch_status", function () {
				var selected_text = ed.selection.getContent();
				var return_text = '[twitch-status channel="' + selected_text + '"]';
				ed.execCommand( "mceInsertContent", 0, return_text );
			} );

		},
		createControl: function ( n, cm ) {
			return null;
		},
		getInfo: function () {
			return {
				longname: "Twitch Status",
				author: "nicolas.bernier",
				version: "1"
			};
		}
	} );

	tinymce.PluginManager.add( "twitch_status_plugin", tinymce.plugins.twitch_status_plugin );
} )();