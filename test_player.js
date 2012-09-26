a=new MusicBox.Player.Track({PreviewUrl: 'http://streamer.nmusic.sapo.pt/previews/2a-3-000-000-303-639-2L/USC7R1200007'});
b=new MusicBox.Player.Track({PreviewUrl: 'http://streamer.nmusic.sapo.pt/previews/2a-3-000-000-303-639-2L/USC7R1200019'});
MusicBox.Player.addTrack(a);
MusicBox.Player.addTrackToPlaylist(a);
MusicBox.Player.addTrackToPlaylist(b);
MusicBox.Player.play()