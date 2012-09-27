$(document).ready(function() {
	//$(':root').addClass(( "ontouchstart" in window ) ? 'touch' : 'no-touch');

	//$(":root > body").css({height: '2000px'});
	//setTimeout(function() { window.scrollTo(0,1); }, 2000);

	//$('#app > footer').css({top: (window.innerHeight-54)})
	$('#main').css({height: (window.innerHeight-50-50)});
	$('#search').css({height: (window.innerHeight-50-50)});
	$('#playlist').css({height: (window.innerHeight-50-80)});
	$('#app > footer').css({height: (window.innerHeight)});

	$('nav.pivot_list ul li a').bind('click', function(e){
		e.preventDefault();
		$('nav.pivot_list ul li a.selected').removeClass('selected');
		$(this).addClass('selected');
	});

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

	$('a[href="#clear_playlist"]').bind('click', function(e){
		e.preventDefault();
		var answer = window.confirm("Tem a certeza que deseja limpar a sua playlist?")
		if (answer) {
			$('.playlist').hide();
			$(':root > body').removeClass('has_playlist');
		}
		else {
			return;
		}
	});

	$('a[href="#toggle_player"]').bind('click', function(e){
		e.preventDefault();
		if ($(':root > body').hasClass('menu_mode')) {
			return;
		}
		var translation = 0;
		if (!$(':root > body').hasClass('player_mode')) {
			translation = -($('#main').height() + $('#app > header').height());
		}
		$(':root > body').toggleClass('player_mode');
		$('#app > footer').css({'-webkit-transform': 'translate3d(0,' + translation + 'px,0)'});
	});

	$('a[href="#toggle_shuffle"]').bind('click', function(e){
		e.preventDefault();
		$(this).parent().toggleClass('active');
	});

	$('a[href="#toggle_repeat"]').bind('click', function(e){
		e.preventDefault();
		var parent = $(this).parent();
		if (parent.hasClass('active')) {
			if (parent.hasClass('repeat_one')) {
				parent.removeClass('active repeat_one');
			} else {
				parent.addClass('repeat_one');
			}
		} else {
			parent.addClass('active');
		}
	});

	$(document).on('click.gui', 'a[href="#toggle_options"]', function(e){
		e.preventDefault();
		var isActive = $(this).parent().hasClass('active');
		$('.tracklist li.active').removeClass('active');
		if (!isActive) $(this).parent().addClass('active');
	});
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
 * Controller Login
 */
(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Controller.Login', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var contextName = "Login", publicInterface = {}, storage = window.localStorage, stogareKey = 'credential';

	publicInterface.performeLogin = function(credentialObj) {
		MusicBox.Service.Auth.login(credentialObj.username, credentialObj.password); // sync request
		MusicBox.Controller.setCurrentContext('Dashboard');
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
	var contextName = "Dashboard", publicInterface = {};

	publicInterface.renderNewAlbuns = function() {
		MusicBox.Service.Content.GetNewAlbums(function(data){
			var template =  MusicBox.getParticalTemplate('albuns_list');
			var view = {
				AlbumList: 	data.AlbumList,
				id: 		'new_albuns'
			};

			$('#new_albuns').replaceWith(Mustache.render(template, view, MusicBox.getParticalTemplate()));
		});
	};

	publicInterface.renderRecommendedAlbuns = function() {
		MusicBox.Service.Content.GetRecommendedAlbums(function(data){
			var template =  MusicBox.getParticalTemplate('albuns_list');
			var view = {
				AlbumList: 	data.AlbumList,
				id: 		'recommended_albuns'
			};

			$('#recommended_albuns').replaceWith(Mustache.render(template, view, MusicBox.getParticalTemplate()));
		});
	};

	$(document).ready(function(){
		// bind events and actions
		$(':root > body').bind('active.'+contextName, function(e){
			console.log("active context Dashboard");
			publicInterface.renderNewAlbuns();
			publicInterface.renderRecommendedAlbuns();
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
	var contextName = "Album", publicInterface = {};

	publicInterface.renderAlbum = function(albumId) {
		MusicBox.Service.Content.GetAlbumById(albumId, function(data){
			var template =  MusicBox.getParticalTemplate('album_detail_header');
			var view = {
				Album: 	data.Album
			};

			$('#album_detail > header').html(Mustache.render(template, view, MusicBox.getParticalTemplate()));
		});
	};

	publicInterface.renderAlbumTrackList = function(albumId) {
		MusicBox.Service.Content.GetTracksByAlbumId(albumId, function(data){
			var template =  MusicBox.getParticalTemplate('album_track_list');
			var view = {
				TrackList: 	data.TrackList.Track.map(function(i){i.toJson = function() {return JSON.stringify(i);}; return i;})
			};

			$('#album_detail > ul.album_tracklist').html(Mustache.render(template, view, MusicBox.getParticalTemplate()));
		});
	};

	$(document).ready(function(){
		// bind events and actions
		$(':root > body').bind('active.'+contextName, function(e){
			console.log("active context Album");
		});

		$(document).on('click.'+contextName, 'a[href="#album"][data-albumId]', function(){
			var albumId = $(this).attr('data-albumId');
			publicInterface.renderAlbum(albumId);
			publicInterface.renderAlbumTrackList(albumId);
			MusicBox.Controller.setCurrentContext(contextName);
		});
	});

	return publicInterface;
})());


(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Player', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var publicInterface = {}, 
		playlist = [], 
		currentIdx = -1,
		options = {
			repeat: true,
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
	};

	publicInterface.Track.prototype.getUrl = function () { return this.Url; };
	publicInterface.Track.prototype.is_playing = function() { return ($(audio).children().attr('src') == this.Url); }
	publicInterface.Track.prototype.toJson = function() { return JSON.stringify(this); }

	publicInterface.getPlayList = function () { return playlist; };

	publicInterface.addTrackToPlaylist = function(track) {
		$(':root > body').addClass('has_playlist');
		playlist.push(track);
	};

	publicInterface.addTrackToPlaylistAfterCurrent = function(track) {
		$(':root > body').addClass('has_playlist');
		var idx = currentIdx;
		playlist.splice(++idx,0,track);
	}

	publicInterface.clearPlaylist = function() {
		$(':root > body').removeClass('has_playlist');
		playlist = [];
		currentIdx = -1;
	};

	publicInterface.play = function() {
		if (playlist.length == 0) return; // dont do anything
		playCurrentMusic();
		loadNextTimeout = setTimeout(loadNextMusic, 1000); // after 1 sec start load new music		
	};

	publicInterface.skip = function() {
		if (playlist.length == 0) return; // dont do anything
		currentIdx++;
		playCurrentMusic(true);
		loadNextTimeout = setTimeout(loadNextMusic, 1000); // after 1 sec start load new music
	};

	publicInterface.previews = function() {
		if (playlist.length == 0) return; // dont do anything
		$('#app > footer > .player > nav.controls .play_pause').addClass('play');
		currentIdx--;
		playCurrentMusic(true);
		loadNextTimeout = setTimeout(loadNextMusic, 1000); // after 1 sec start load new music
	};

	playCurrentMusic = function (controller) {
		clearTimeout(loadNextTimeout); // if have any loadNextMusic scheduled stop it
		if (currentIdx >= playlist.length) currentIdx = 0;
		$(audio).children().attr('src', playlist[currentIdx].getUrl());
		$('#app > footer .mini_player').addClass('loading');
		audio.load();
		audio.play();
		if ($(':root > body').hasClass('player_mode')) { renderPlaylist(); } // if in player mode render playlist
	};

	loadNextMusic = function() {
		if (!options.repeat) return;
		var nextIdx = currentIdx + 1;
		if (nextIdx >= playlist.length) nextIdx = 0;
		$(preLoad).children().attr('src', playlist[nextIdx].getUrl());
		preLoad.load();
	};

	renderPlayerPlay = function() {
		var template =  MusicBox.getParticalTemplate('mini_player');
		var view = {
			Track: 	playlist[currentIdx]
		};

		$('#app > footer > .mini_player').html(Mustache.render(template, view, MusicBox.getParticalTemplate())).removeClass('loading');
		$('#app > footer > .player > nav.controls .play_pause').removeClass('play');
	};

	renderPlaylist = function() {
			var template =  MusicBox.getParticalTemplate('playlist_list');
			var view = {
				TrackList: 	playlist
			};
			$('#playlist').html(Mustache.render(template, view, MusicBox.getParticalTemplate()));
	};

	$(document).ready(function(){
		var source;
		audio = document.getElementById('player');
		source = document.createElement('source');
		source.setAttribute('type', 'audio/mpeg');
		audio.appendChild(source);

		// player events
		$(audio).bind('ended.player', publicInterface.skip); // at end start next
		$(audio).bind('play.miniplayer', renderPlayerPlay);
		$(audio).bind('progress.miniplayer', function(e){
			if ((audio.buffered != undefined) && (audio.buffered.length != 0)) {
			    var loaded = parseInt(((audio.buffered.end(0) / audio.duration) * 100), 10);
			    $('#app > footer nav.time_bars .buffered').css({width: loaded + '%'});
			}
		});
		$(audio).bind('timeupdate.miniplayer', function(e){
			if ((audio.currentTime != undefined)) {
			    var played = parseInt(((audio.currentTime / audio.duration) * 100), 10);
			    $('#app > footer nav.time_bars .elapsed_time').css({width: played + '%'});
			}
		});


		preLoad = document.createElement('audio');
		source = document.createElement('source');
		source.setAttribute('type', 'audio/mpeg');
		preLoad.appendChild(source);
		//href="#play" data-element="play"

		// Events
		$(document).on('click.player_gui', 'a[data-element="play"]', function(e){
			e.preventDefault();
			var data = $(this).attr('data-json') || $(this).parents('[data-json]').attr('data-json');
			publicInterface.addTrackToPlaylistAfterCurrent(new MusicBox.Player.Track(JSON.parse(data)));
			publicInterface.skip();
		});
		$('a[data-element="play_album"]').bind('click.player_gui', function(e){
			e.preventDefault();
			var data = $("#album_detail ul.album_tracklist [data-json]");
			if (data.length == 0) return;
			for(var i = data.length - 1; i >= 0; --i) {
				var track_json = data.get(i).getAttribute('data-json');
				publicInterface.addTrackToPlaylistAfterCurrent(new MusicBox.Player.Track(JSON.parse(track_json)));
			}
			publicInterface.skip();
		});

		$('a[data-element="toggle_player"]').bind('click.player_gui', function(e){ // open player -> render playlist
			e.preventDefault();
			if ($(':root > body').hasClass('menu_mode')) {
				return;
			}
			renderPlaylist();
		});

		$('a[data-element="toggle_play"]').bind('click.player_gui', function(e){
			e.preventDefault();
			if (audio.paused) { audio.play(); }
			else { audio.pause(); $(this).parent().addClass('play'); }
		});
		$('[data-element="previous"]').bind('click.player_gui', function(e){ 
			e.preventDefault(); 
			publicInterface.previews(); 
		});
		$('[data-element="skip"]').bind('click.player_gui', function(e){ 
			e.preventDefault(); 
			publicInterface.skip(); 
		});
	});
	return publicInterface;
})());
