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

	$('a[href="#toggle_options"]').bind('click', function(e){
		e.preventDefault();
		var isActive = $(this).parent().hasClass('active');
		$('.tracklist li.active').removeClass('active');
		if (!isActive) $(this).parent().addClass('active');
	});
});


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
	}

	$(document).ready(function(){
		exportObj.garbagePartialTemplates();
	});
	scope.MusicBox = exportObj;
})(window)