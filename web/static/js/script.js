$(document).ready(function() {
	//$(':root').addClass(( "ontouchstart" in window ) ? 'touch' : 'no-touch');

	//$(":root > body").css({height: '2000px'});
	//setTimeout(function() { window.scrollTo(0,1); }, 2000);

	//$('#app > footer').css({top: (window.innerHeight-54)})
	$('#main').css({height: (window.innerHeight-50-50)});
	$('#playlist').css({height: (window.innerHeight-50-50)});
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

	$('a[href="#toggle_play"]').bind('click', function(e){
		e.preventDefault();
		$('.controls .play_pause').toggleClass('play');
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
		//window.scrollTo(0, 1);
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

			$('#album > header').html(Mustache.render(template, view, MusicBox.getParticalTemplate()));
		});
	};

	publicInterface.renderAlbumTrackList = function(albumId) {
		MusicBox.Service.Content.GetTracksByAlbumId(albumId, function(data){
			var template =  MusicBox.getParticalTemplate('album_track_list');
			var view = {
				TrackList: 	data.TrackList.Track.map(function(i){i.toJson = function() {return JSON.stringify(i);}; return i;})
			};

			$('#album > ul.album_tracklist').html(Mustache.render(template, view, MusicBox.getParticalTemplate()));
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

	publicInterface.addTrackToPlaylist = function(track) {
		playlist.push(track);
	};

	publicInterface.clearPlaylist = function() {
		playlist = [];
		currentIdx = -1;
	};

	publicInterface.startNext = function() {
		if (playlist.length == 0) return; // dont do anything
		currentIdx++;
		playCurrentMusic();
		loadNextTimeout = setTimeout(loadNextMusic, 1000); // after 1 sec start load new music
	};

	publicInterface.playMusic = function(track) {
		var repeat = options.repeat; 
		options.repeat =false;
		$(audio).children().attr('src', track.getUrl());
		audio.load();
		audio.play();

		// reset repeat options state
		$(audio).one('ended', function() { setTimeout(function(){ options.repeat = repeat; }, 250); });
	};

	playCurrentMusic = function () {
		clearTimeout(loadNextTimeout); // if have any loadNextMusic scheduled stop it
		if (currentIdx >= playlist.length) currentIdx = 0;
		$(audio).children().attr('src', playlist[currentIdx].getUrl());
		audio.load();
		audio.play();
	};

	loadNextMusic = function() {
		if (!options.repeat) return;
		var nextIdx = currentIdx + 1;
		if (nextIdx >= playlist.length) nextIdx = 0;
		$(preLoad).children().attr('src', playlist[nextIdx].getUrl());
		preLoad.load();
	};

	$(document).ready(function(){
		var source;
		audio = document.createElement('audio');
		source = document.createElement('source');
		source.setAttribute('type', 'audio/mpeg');
		audio.appendChild(source);
		$(audio).bind('ended.player', publicInterface.startNext); // at end start next

		preLoad = document.createElement('audio');
		source = document.createElement('source');
		source.setAttribute('type', 'audio/mpeg');
		preLoad.appendChild(source);
		//href="#play" data-element="play"
	});
	return publicInterface;
})());

(function(runtime){
	if (typeof window.MusicBox === 'object') {
		window.MusicBox.register('Player.GUI', runtime);
	} else {
		throw "MusicBox not defined";
	}
})((function(){
	var publicInterface = {};

	var playMusic = function(e) {
		e.preventDefault();
		var data = $(this).attr('data-json') || $(this).parents('[data-json]').attr('data-json');
		MusicBox.Player.playMusic(new MusicBox.Player.Track(JSON.parse(data)));
	}

	$(document).ready(function(){
		$(document).on('click.Player', 'a[href="#play"][data-element="play"]', playMusic);
	});
	return publicInterface;
})());