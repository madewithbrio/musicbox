$(document).ready(function() {
	//$(':root').addClass(( "ontouchstart" in window ) ? 'touch' : 'no-touch');

	//$(":root > body").css({height: '2000px'});
	//setTimeout(function() { window.scrollTo(0,1); }, 2000);

	//$('#app > footer').css({top: (window.innerHeight-54)})
	$('#content').css({height: (window.innerHeight-50)});
	$('#search').css({height: (window.innerHeight-50-50)});
	$('#playlist').css({height: (window.innerHeight-50-80)});
	//$('#dashboard > .scrollable').css({height: (window.innerHeight-50-50-73)});
	$('#app > footer').css({height: (window.innerHeight)});

	$('a[href="#search"]').bind('click', function(e){
		e.preventDefault();
		$(":root > body").toggleClass('search_mode');
		$(":root > body").removeClass('menu_mode');
	});

	$('a[href="#user"]').bind('click', function(e){
		e.preventDefault();
		$(":root > body").toggleClass('menu_mode');
		$(":root > body").removeClass('search_mode');
	});

	$('a[href="#menu"]').bind('click', function(e){
		e.preventDefault();
		$(":root > body").toggleClass('menu_mode');
		$(":root > body").removeClass('search_mode');
	});

	$('a.mode').bind('click', function(e){
		e.preventDefault();
		$(":root > body").toggleClass('online');
	});

	$(document).on('click.gui', 'a[href="#toggle_options"]', function(e){
		e.preventDefault();
		var isActive = $(this).parent().hasClass('active');
		$('.tracklist li.active').removeClass('active');
		if (!isActive) $(this).parent().addClass('active');
	});

	window.scrollTo(0, 1);
});

/**
 *  Main object
 */
(function(scope){
	var exportObj = {}, partialTemplates = {};

	exportObj.getParticalTemplate = function(name) {
		if (typeof name === "string" && typeof partialTemplates[name] === "string") { return partialTemplates[name] }
		else if (typeof name === "string") throw "template not defined";
		return partialTemplates;
	};

	exportObj.garbagePartialTemplates = function() {
		var templates = document.querySelectorAll('script[data-element="template"][type="text/mustache"]');
		templates.forEach = [].forEach;
		templates.forEach(function(element) {
			var name = element.getAttribute('data-name');
			partialTemplates[name] = element.innerHTML;
		});
	};

	exportObj.register = function(namespace, module) {
		var namespaces = namespace.split("."), i, b;
		for (i = 0; i <= namespaces.length;++i) {
			b = namespaces.slice(0, i+1).join('\'][\'');
			if (i < namespaces.length) {
				eval('if (typeof exportObj[\''+b+'\'] === \'undefined\') { exportObj[\''+b+'\'] = {}; }');
			} else {
				eval('exportObj[\''+b+'\'] = module');
			}
		}
	};

	exportObj.convertDecimalToMinSec = function(decimal) {
		var hours = Math.floor(decimal/3600,10),
			mins  = Math.floor((decimal - hours*60)/60,10),
  		    secs  = Math.floor(decimal - mins*60);
  		if (mins < 10) mins = "0" + mins;  
  		if (secs < 10) secs = "0" + secs;
  		if (hours > 0) mins = hours + ":" + mins;
  		return mins+":"+secs;
	}
	$(document).ready(function(){
		exportObj.garbagePartialTemplates();
	});

	scope.MusicBox = exportObj;
})(window);

/**
 *  Service Auth proxy
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Service.Auth', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var service_interface = {},
		service_endpoint = "/auth";

	var call = function(parameters, callback) {
		var result;
		var async = typeof callback === "function";
		if (!async) callback = function(data){result=data;};

		var xhr = $.ajax({
			type: "POST",
			async: async,
			url: service_endpoint,
			data: parameters,
			success: callback,
			dataType: 'json'
		});
		return result;
	};

	service_interface.login = function(username, password, callback) {
		return call({username: username, password: password}, callback);
	};
	return service_interface;
})());

/**
 *  Service Content proxy
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Service.Content', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var service_interface = {},
		service_endpoint = "/content";

	var call = function(method, parameters, callback) {
		var result;
		var async = typeof callback === "function";
		if (!async) callback = function(data){result=data;};

		var callbackProxy = function(data) {
			if (data.Status.ErrorCode > 0) {
				console.log(data.Status.ErrorDesc);
				throw data.Status.ErrorDesc
			} else {
				callback.call(null, data);
			}
		};

		var xhr = $.ajax({
			type: "POST",
			async: async,
			cache: false,
			url: service_endpoint + "?method="+method,
			data: parameters,
			success: callbackProxy,
			dataType: 'json'
		});
		
		return result;
	};

	service_interface.GetNewAlbums = function(callback) {
		return call('GetNewAlbums', {}, callback);
	};

	service_interface.GetRecommendedAlbums = function(callback) {
		return call('GetRecommendedAlbums', {}, callback);
	};

	service_interface.GetAlbumById = function(AlbumId, callback) {
		return call('GetAlbumById', {
			AlbumId: AlbumId
		}, callback);
	};

	service_interface.GetTracksByAlbumId = function(AlbumId, callback) {
		return call('GetTracksByAlbumId', {
			AlbumId: AlbumId
		}, callback);
	};

	return service_interface;
})());

/**
 *	Main Controller
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Controller', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var publicInterface = {};

	publicInterface.getCurrentContext = function() {
		return $(':root > body').attr('data-context');
	};

	publicInterface.setCurrentContext = function(context) {
		$(':root > body').attr('data-context', context).trigger('active.'+context);
	}

	$(document).ready(function(){
	});

	return publicInterface;
})());

/**
 * Controller Menu
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Controller.Menu', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var publicInterface = {};

	$(document).ready(function(){

		$('a[data-element="context_playlist"]').bind('click.Menu', function(e){ // open player from menu
			e.preventDefault();
			if ($(':root > body').hasClass('player_mode')) {
				return;
			}
			$('#menu .editorial_menu .selected').removeClass('selected');
			$(this).parent().addClass('selected');
			$(":root > body").removeClass('menu_mode').trigger('active.Player');
		});

		$('a[data-element="context_dashboard"]').bind('click.player_gui', function(e){ // open player from menu
			e.preventDefault();
			$('#menu .editorial_menu .selected').removeClass('selected');
			$(this).parent().addClass('selected');
			MusicBox.Controller.setCurrentContext('Dashboard');
		});
	});

	return publicInterface;
})());

/**
 * Controller Login
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Controller.Login', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var contextName = "login", publicInterface = {}, storage = window.localStorage, stogareKey = 'credential';

	publicInterface.performeLogin = function(credentialObj) {
		MusicBox.Service.Auth.login(credentialObj.username, credentialObj.password); // sync request
		MusicBox.Controller.setCurrentContext('dashboard');
	};

	$(document).ready(function(){
		var credential = storage.getItem(stogareKey);
		if (typeof credential === 'string') {
			var credentialObj = JSON.parse(credential);
			setTimeout(function() { publicInterface.performeLogin(credentialObj); }, 250);
		}

		// bind events and actions
		$('#login form').bind('submit.controller', function(e){
			var username, password;
			e.preventDefault();
			var credentialObj = {};
			credentialObj.username = $('#login form input[name="username"]').val();
			credentialObj.password = $('#login form input[name="password"]').val();
			storage.setItem(stogareKey, JSON.stringify(credentialObj));
			publicInterface.performeLogin(credentialObj);
		});

		$(':root > body').bind('active.'+contextName, function(e){
			console.log("active context Login");
		});
	});

	return publicInterface;
})());

/**
 * Controller Dashboard
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Controller.Dashboard', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var contextName = "dashboard", publicInterface = {};

	publicInterface.renderNewAlbuns = function() {
		MusicBox.Service.Content.GetNewAlbums(function(data){
			var template =  MusicBox.getParticalTemplate('albuns_list');
			var view = {
				AlbumList: 	data.AlbumList.Album.map(function(i){
					i.toJson = function() {return JSON.stringify(i);};
					i.timeDuration = function () {return MusicBox.convertDecimalToMinSec(i.Duration);};
					return i;
				}),
				id: 		'new_albuns'
			};

			$('#new_albuns').replaceWith(Mustache.render(template, view, MusicBox.getParticalTemplate()));
		});
	};

	publicInterface.renderRecommendedAlbuns = function() {
		MusicBox.Service.Content.GetRecommendedAlbums(function(data){
			var template =  MusicBox.getParticalTemplate('albuns_list');
			var view = {
				AlbumList: 	data.AlbumList.Album.map(function(i){
					i.toJson = function() {return JSON.stringify(i);};
					i.timeDuration = function () {return MusicBox.convertDecimalToMinSec(i.Duration);};
					return i;
				}),
				id: 		'new_albuns'
			};

			$('#recommended_albuns').replaceWith(Mustache.render(template, view, MusicBox.getParticalTemplate()));
		});
	};

	$(document).ready(function(){
		// bind events and actions
		$(':root > body').bind('active.'+contextName, function(e){
			console.log("active context Dashboard");
			publicInterface.renderNewAlbuns();
		//	publicInterface.renderRecommendedAlbuns();
		});
	});

	return publicInterface;
})());

/**
 * Controller Album
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Controller.Album', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var contextName = "album_detail", publicInterface = {};

	publicInterface.renderAlbum = function(albumId, albumData) {
		var renderAlbumGui = function (album) {
			var template =  MusicBox.getParticalTemplate('album_detail_header');
			album.timeDuration = function () {return MusicBox.convertDecimalToMinSec(album.Duration);};
			var view = {
				Album: 	album
			};

			$('#album_detail > header').html(Mustache.render(template, view, MusicBox.getParticalTemplate()));			
		}
		if (typeof albumData === 'undefined') { // dont have data go to service and get it
			MusicBox.Service.Content.GetAlbumById(albumId, function(data){
				renderAlbumGui(data.Album);
			});
		} else { // we have allready data and we go use it
			renderAlbumGui(JSON.parse(albumData));
		}
	};

	publicInterface.renderAlbumTrackList = function(albumId) {
		MusicBox.Service.Content.GetTracksByAlbumId(albumId, function(data){
			var template =  MusicBox.getParticalTemplate('album_track_list');
			var view = {
				TrackList: 	data.TrackList.Track.map(function(i){
					i.toJson = function() {return JSON.stringify(i);};
					i.timeDuration = function () {return MusicBox.convertDecimalToMinSec(i.Duration);};
					return i;
				})
			};

			$('#album_detail > ul.album_tracklist').html(Mustache.render(template, view, MusicBox.getParticalTemplate()));
		});
	};

	$(document).ready(function(){
		// bind events and actions
		$(':root > body').bind('active.'+contextName, function(e){
			console.log("active context Album");
		});

		$(document).on('click.'+contextName, 'a[data-albumId]', function(){
			var albumId = $(this).attr('data-albumId'), albumJson = $(this).attr('data-json');
			publicInterface.renderAlbum(albumId, albumJson);
			publicInterface.renderAlbumTrackList(albumId);
			MusicBox.Controller.setCurrentContext(contextName);
		});
	});

	return publicInterface;
})());

/**
 * Player
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Player', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var contextName = 'player',
		publicInterface = {}, 
		playlist = [], 
		currentIdx = -1,
		options = {
			shuffle: false,
			repeat: 0,
		},
		audio, preLoad, loadNextTimeout;

	publicInterface.Track = function(object, trackId, url, trackName, artistName, albumName){
		this.TrackId 				= object.TrackId;
		this.TrackName 				= object.TrackName || trackName;
		this.ArtistName 			= object.ArtistName || artistName;
		this.ArtistId 				= object.ArtistId;
		this.AlbumName 				= object.AlbumName || albumName;
		this.AlbumId 				= object.AlbumId;
		this.Duration 				= object.Duration;
		this.AlbumCover 			= object.LargeAlbumCover;
		this.AlbumNumberOfVolumes 	= object.AlbumNumberOfVolumes;
		this.VolumeIndex 			= object.VolumeIndex;
		this.TrackIndex 			= object.TrackIndex;
		this.RecordLabel 			= object.RecordLabel;
		this.Url 					= object.PreviewUrl || url;
		this.is_playing 			= false;
	};

	publicInterface.Track.prototype.getUrl = function () { return this.Url; };
	//publicInterface.Track.prototype.is_playing = function() { return ($(audio).children().attr('src') == this.Url); }
	publicInterface.Track.prototype.toJson = function() { return JSON.stringify(this); }

	publicInterface.getPlayList = function () { return playlist; };

	publicInterface.addTrackToPlaylist = function(track) {
		var first = playlist.length == 0;

		playlist.push(track);
		if (first) { 
			renderPlayerPlay(); 
			$(':root > body').addClass('has_playlist'); 
		}
	};

	publicInterface.addTrackToPlaylistAfterCurrent = function(track) {
		var first = playlist.length == 0;
		
		var idx = currentIdx;
		playlist.splice(++idx,0,track);
		if (first) { 
			renderPlayerPlay();
			currentIdx--;
			$(':root > body').addClass('has_playlist');
			$('#content').css({height: (window.innerHeight-50-50)});
		}
	}

	publicInterface.clearPlaylist = function() { // clean playlist and make sure we have reset interfaces
		var scrubber;
		audio.pause();
		$(audio).children().removeAttr('src');
		playlist = [];
		currentIdx = -1;
		setTimeout(function(){
			$(':root > body').removeClass('has_playlist');
			scrubber = $('#app > footer nav.scrubber');
		    scrubber.find('.elapsed_time,.buffered').css({width: '0%'})
		    scrubber.find('.elapsed_time_counter').html("");
		    scrubber.find('.total_time').html("");
		}, 250);
	};

	publicInterface.play = function() {
		if (playlist.length == 0) return; // dont do anything
		playCurrentMusic(true);
	};

	publicInterface.skip = function() {
		if (playlist.length == 0) return; // dont do anything
		currentIdx++;
		playCurrentMusic(!audio.paused);
	};

	publicInterface.previews = function() {
		if (playlist.length == 0) return; // dont do anything
		//$('#app > footer > .player > nav.controls .play_pause').addClass('play');
		if (!audio.paused && audio.currentTime > 3) // the track is on start and audio is playing
		{
			audio.currentTime = 0;
		}
		else
		{
			if (options.repeat != 1 && currentIdx == 0) {
				audio.currentTime = 0;
			} else {
				currentIdx--;
				playCurrentMusic(!audio.paused);
			}
		}
	};

	validateCurrentIndex = function () {
		if (currentIdx >= playlist.length && options.repeat == 1) 	{ currentIdx = 0; }
		else if (currentIdx >= playlist.length) 					{ currentIdx = playlist.length - 1; }
		else if (currentIdx < 0 && options.repeat == 1) 			{ currentIdx = playlist.length - 1; }
		else if (currentIdx < 0) 									{ currentIdx = 0; }
	};

	playCurrentMusic = function (startPlay) {
		if (playlist.length == 0) return;
		validateCurrentIndex();
		audio.pause();

		$(audio).children().attr('src', playlist[currentIdx].getUrl());
		$('#mini_player').addClass('loading');
		audio.load();
		if (startPlay) { audio.play(); }
		if ($(':root > body').hasClass('player_mode')) { renderPlaylist(); } // if in player mode render playlist
	};

	renderPlayerPlay = function() {
		validateCurrentIndex();
		var template =  MusicBox.getParticalTemplate('mini_player'),
			repeatButton = $('a[data-element="toggle_repeat"]').parent();
			view = {
				Track: 	playlist[currentIdx]
			};

		$('#mini_player').html(Mustache.render(template, view, MusicBox.getParticalTemplate())).removeClass('loading');
		if (audio.paused) {
			$('#app > footer a[data-element="toggle_play"]').parent().addClass('play');
		} else {
			$('#app > footer a[data-element="toggle_play"]').parent().removeClass('play');
		}

		if (options.repeat == 0) { repeatButton.removeClass('active repeat_one'); }
		else if (options.repeat == 1) { repeatButton.removeClass('repeat_one').addClass('active'); }
		else if (options.repeat == 2) { repeatButton.addClass('active repeat_one'); }
	};

	renderPlaylist = function() {
			var template =  MusicBox.getParticalTemplate('playlist_list');
			var playlistToRender = playlist.map(function(i){ i.is_playing = false; return i; });
			if (typeof currentIdx == 'number' && currentIdx >= 0 && currentIdx < playlistToRender.length){
				playlistToRender[currentIdx].is_playing = true;
			}
			var view = {
				TrackList: 	playlistToRender
			};
			$('#playlist').html(Mustache.render(template, view, MusicBox.getParticalTemplate()));
	};

	togglePlayer = function() {
		var translation = 0;
		if (!$(':root > body').hasClass('player_mode')) {
			translation = -($('#content').height() + $('#app > header').height());
		}
		$(':root > body').toggleClass('player_mode');
		$('#app > footer').css({'-webkit-transform': 'translate3d(0,' + translation + 'px,0)'});
	};

	displayNotification = function(text) {
		$('#notification').html(text).show();
		$('#notification').delay(1000).fadeOut(500);
	};

	$(document).ready(function(){
		var source;
		audio = document.getElementById('player');
		source = document.createElement('source');
		source.setAttribute('type', 'audio/mpeg');
		audio.appendChild(source);

		// player events
		$(audio).bind('ended.player', function() { // at end start next
			$('a[data-element="toggle_play"]').parent().addClass('play');
			if (options.repeat == 0 && currentIdx+1 >= playlist.length) { return; } // end playlist
			if (options.repeat != 2) { publicInterface.skip(); } // load next
			this.play(); // play audio
		});
		$(audio).bind('play.miniplayer', renderPlayerPlay);
		$(audio).bind('progress.miniplayer', function(e){
			if ((audio.buffered != undefined) && (audio.buffered.length != 0)) {
			    var loaded = parseInt(((audio.buffered.end(0) / audio.duration) * 100), 10);
			    $('#app > footer nav.scrubber .buffered').css({width: loaded + '%'});
			}
		});
		$(audio).bind('timeupdate.miniplayer', function(e){
			var played, scrubber;
			if ((audio.currentTime != undefined)) {
			    played = parseInt(((audio.currentTime / audio.duration) * 100), 10);
			    scrubber = $('#app > footer nav.scrubber');
			    scrubber.find('.elapsed_time').css({width: played + '%'});
			    scrubber.find('.elapsed_time_counter').html(MusicBox.convertDecimalToMinSec(audio.currentTime));
			    scrubber.find('.total_time').html(MusicBox.convertDecimalToMinSec(audio.duration));
			}
		});

		// Events
		$(':root > body').bind('active.'+contextName, function(e){
			console.log("active context Playlist");
			renderPlaylist();
			togglePlayer();
		});


		// Interface events bind
		$(document).on('click.player_gui', 'a[data-element="play"]', function(e){ // play track
			e.preventDefault();
			var data = $(this).attr('data-json') || $(this).parents('[data-json]').attr('data-json');
			publicInterface.addTrackToPlaylistAfterCurrent(new MusicBox.Player.Track(JSON.parse(data)));
			publicInterface.play();
		});
		$(document).on('click.player_gui', 'a[data-element="queue_next"]', function(e){ // queue track in next position
			e.preventDefault();
			var data = $(this).attr('data-json') || $(this).parents('[data-json]').attr('data-json');
			publicInterface.addTrackToPlaylistAfterCurrent(new MusicBox.Player.Track(JSON.parse(data)));
			displayNotification('Faixa vai tocar a seguir');
		});
		$(document).on('click.player_gui', 'a[data-element="queue_last"]', function(e){ // queue track in last position
			e.preventDefault();
			var data = $(this).attr('data-json') || $(this).parents('[data-json]').attr('data-json');
			publicInterface.addTrackToPlaylist(new MusicBox.Player.Track(JSON.parse(data)));
			displayNotification('Faixa adicionada ao final da playlist');
		});
		$('a[data-element="play_album"]').bind('click.player_gui', function(e){ // play album
			e.preventDefault();
			var data = $("#album_detail ul.album_tracklist [data-json]");
			if (data.length == 0) return;
			for(var i = data.length - 1; i >= 0; --i) {
				var track_json = data.get(i).getAttribute('data-json');
				publicInterface.addTrackToPlaylistAfterCurrent(new MusicBox.Player.Track(JSON.parse(track_json)));
			}
			publicInterface.play(); 
		});
		$('a[data-element="queue_next_album"]').bind('click.player_gui', function(e){ // queue album in next position
			e.preventDefault();
			var data = $("#album_detail ul.album_tracklist [data-json]");
			if (data.length == 0) return;
			for(var i = data.length - 1; i >= 0; --i) {
				var track_json = data.get(i).getAttribute('data-json');
				publicInterface.addTrackToPlaylistAfterCurrent(new MusicBox.Player.Track(JSON.parse(track_json)));
			}
			displayNotification('Álbum vai tocar a seguir');
		});
		$('a[data-element="queue_last_album"]').bind('click.player_gui', function(e){ // queue album in last position
			e.preventDefault();
			var data = $("#album_detail ul.album_tracklist [data-json]");
			if (data.length == 0) return;
			for(var i = 0; i < data.length; ++i) {
				var track_json = data.get(i).getAttribute('data-json');
				publicInterface.addTrackToPlaylist(new MusicBox.Player.Track(JSON.parse(track_json)));
			}
			displayNotification('Álbum adicionado ao final da playlist');
		});
		$('a[data-element="clear_playlist"]').bind('click.player_gui', function(e){ // clear playlist
			e.preventDefault();
			var answer = window.confirm("Tem a certeza que deseja limpar a sua playlist?")
			if (answer) {
				publicInterface.clearPlaylist();
				$('.playlist').hide();
				$(':root > body').removeClass('has_playlist');
				setTimeout(function() { renderPlaylist(); }, 250);
			}
		});

		$('a[data-element="toggle_player"]').bind('click.player_gui', function(e){ // open player -> render playlist
			e.preventDefault();
			if ($(':root > body').hasClass('menu_mode')) {
				return;
			}
			if (!$(':root > body').hasClass('player_mode')) { renderPlaylist(); }
			togglePlayer();
		});

		$('a[data-element="toggle_play"]').bind('click.player_gui', function(e){ // play | pause
			e.preventDefault();
			if (audio.currentSrc.length == 0) { 
				playCurrentMusic(true); 
			} else if (audio.paused) { 
				audio.play(); 
			} else { 
				audio.pause(); 
				$(this).parent().addClass('play'); 
			}
		});
		$('[data-element="previous"]').bind('click.player_gui', function(e){ // previews | restart track
			e.preventDefault(); 
			publicInterface.previews(); 
		});
		$('[data-element="skip"]').bind('click.player_gui', function(e){ // next
			e.preventDefault(); 
			publicInterface.skip(); 
		});
		$('a[data-element="toggle_repeat"]').bind('click', function(e){ // repeat | repeat one
			e.preventDefault();
			var parent = $(this).parent();
			if (parent.hasClass('active')) {
				if (parent.hasClass('repeat_one')) {
					parent.removeClass('active repeat_one');
					options.repeat = 0;
				} else {
					parent.addClass('repeat_one');
					options.repeat = 2;
				}
			} else {
				parent.addClass('active');
				options.repeat = 1;
			}
		});

		$('a[href="#toggle_shuffle"]').bind('click', function(e){
			e.preventDefault();
			$(this).parent().toggleClass('active');
		});

	});
	return publicInterface;
})());
